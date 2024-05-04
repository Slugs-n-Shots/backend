<?php

namespace App\Console\Commands;

use App\Models\Drink;
use App\Models\DrinkCategory;
use App\Models\DrinkUnit;
use File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDrinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:drinks {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import drinks from a JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        if (!file_exists($filePath)) {
            $this->error("File not found at $filePath");
            return;
        }

        $jsonContents = File::get($filePath);
        $drinks = json_decode($jsonContents);

        DB::beginTransaction();
        try {
            foreach ($drinks as $data) {
                $category = DrinkCategory::firstOrCreate(['name_en' => $data->category_en]);

                $drink = Drink::firstOrCreate([
                    'name_en' => $data->en
                ], [
                    'name_en' => $data->en,
                    'name_hu' => $data->hu,
                    'description_en' => $data->description_en ?? null,
                    'description_hu' => $data->description_hu ?? null,
                    'category_id' => $category->id,
                    'active' => $data->active,
                ]);

                foreach ($data->units as $unit) {
                    DrinkUnit::firstOrCreate([
                        'drink_id' => $drink->id,
                        'quantity' => $unit->quantity,
                        'unit_en' => $unit->unit_en,
                    ], [
                        'drink_id' => $drink->id,
                        'quantity' => $unit->quantity,
                        'unit_en' => $unit->unit_en,
                        'unit_hu' => $unit->unit_hu,
                        'unit_price' => $unit->unit_price,
                        'active' => true,
                    ]);
                }
            }
            DB::commit();
            $this->info("Drinks imported successfully.");
        } catch (\Exception $e) {
            DB::rollback();
            $this->error("An error occurred: " . $e->getMessage());
        }
    }
}
