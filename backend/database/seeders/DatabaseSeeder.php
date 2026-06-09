<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Diamond;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // 0. Seed admin users
        \App\Models\User::create([
            'name' => 'OM Normal Admin',
            'email' => 'admin@omgems.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'normal_admin',
        ]);

        \App\Models\User::create([
            'name' => 'OM Super Admin',
            'email' => 'super@omgems.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'super_admin',
        ]);

        // 1. Seed dropdown categories
        $categories = [
            'shape' => ['Round', 'Pear', 'Princess', 'Marquise', 'Emerald', 'Cushion Brilliant', 'Cushion Modified', 'Asscher', 'Sq. Emerald', 'Oval'],
            'color' => ['D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Fancy'],
            'clarity' => ['FL', 'IF', 'VVS1', 'VVS2', 'VS1', 'VS2', 'SI1', 'SI2', 'SI3', 'I1', 'I2', 'I3'],
            'cut' => ['Excellent', 'Very Good', 'Good', 'Fair', 'Poor'],
            'polish' => ['Excellent', 'Very Good', 'Good', 'Fair'],
            'symmetry' => ['Excellent', 'Very Good', 'Good', 'Fair'],
            'lab' => ['GIA', 'IGI', 'HRD', 'None'],
            'fluorescence_intensity' => ['None', 'Faint', 'Medium', 'Strong', 'Very Strong'],
            'fluorescence_color' => ['None', 'Blue', 'Yellow'],
            'girdle_condition' => ['Faceted', 'Bruted', 'Polished'],
            'culet_condition' => ['Pointed', 'Polished'],
            'culet_size' => ['None', 'Very Small', 'Small'],
            'treatment' => ['None', 'HPHT', 'Irradiated', 'Clarity Enhanced']
        ];

        foreach ($categories as $type => $names) {
            Category::create([
                'type' => $type,
                'names' => $names
            ]);
        }

        // 2. Seed mock diamonds
        Diamond::create([
            'stock_no' => 'OM-10023',
            'asking_price' => 12500.00,
            'asking_price_unit' => 'CT',
            'cash_price' => 12100.00,
            'cash_price_unit' => 'CT',
            'availability' => 'Available',
            'country' => 'India',
            'state' => 'Gujarat',
            'city' => 'Surat',
            'shape' => 'Round',
            'size' => 1.520,
            'color' => 'D',
            'clarity' => 'VVS1',
            'cut' => 'Excellent',
            'polish' => 'Excellent',
            'symmetry' => 'Excellent',
            'fluorescence_intensity' => 'None',
            'fluorescence_color' => 'None',
            'length' => 7.32,
            'width' => 7.36,
            'depth' => 4.51,
            'depth_percent' => 61.5,
            'table_percent' => 57.0,
            'girdle_min' => 'Medium',
            'girdle_max' => 'Medium',
            'culet_condition' => 'Pointed',
            'culet_size' => 'None',
            'lab' => 'GIA',
            'report_no' => '2100583921',
            'show_on_OM' => true,
            'report_date' => '2026-05-10',
            'lab_location' => 'Mumbai',
            'status' => 'Approved',
            'created_by' => 'Super Admin'
        ]);

        Diamond::create([
            'stock_no' => 'OM-20155',
            'asking_price' => 7800.00,
            'asking_price_unit' => 'CT',
            'cash_price' => 7550.00,
            'cash_price_unit' => 'CT',
            'availability' => 'Available',
            'country' => 'India',
            'state' => 'Gujarat',
            'city' => 'Surat',
            'shape' => 'Pear',
            'size' => 1.150,
            'color' => 'E',
            'clarity' => 'VVS2',
            'cut' => 'Very Good',
            'polish' => 'Excellent',
            'symmetry' => 'Very Good',
            'fluorescence_intensity' => 'Faint',
            'fluorescence_color' => 'Blue',
            'length' => 8.42,
            'width' => 5.92,
            'depth' => 3.65,
            'depth_percent' => 61.7,
            'table_percent' => 58.0,
            'lab' => 'GIA',
            'report_no' => '6200948271',
            'show_on_OM' => true,
            'report_date' => '2026-04-18',
            'status' => 'Pending',
            'created_by' => 'Normal Admin'
        ]);

        Diamond::create([
            'stock_no' => 'OM-88402',
            'asking_price' => 24500.00,
            'asking_price_unit' => 'CT',
            'cash_price' => 23900.00,
            'cash_price_unit' => 'CT',
            'availability' => 'Memo',
            'country' => 'Belgium',
            'state' => 'Antwerp',
            'city' => 'Antwerp',
            'shape' => 'Round',
            'size' => 2.050,
            'color' => 'D',
            'clarity' => 'IF',
            'cut' => 'Excellent',
            'polish' => 'Excellent',
            'symmetry' => 'Excellent',
            'fluorescence_intensity' => 'None',
            'fluorescence_color' => 'None',
            'length' => 8.12,
            'width' => 8.16,
            'depth' => 5.01,
            'depth_percent' => 61.4,
            'table_percent' => 56.0,
            'lab' => 'HRD',
            'report_no' => 'HRD1900284',
            'show_on_OM' => true,
            'report_date' => '2026-03-01',
            'status' => 'Approved',
            'created_by' => 'Super Admin'
        ]);

        Diamond::create([
            'stock_no' => 'OM-55122',
            'asking_price' => 4500.00,
            'asking_price_unit' => 'CT',
            'cash_price' => 4300.00,
            'cash_price_unit' => 'CT',
            'availability' => 'On Hold',
            'country' => 'India',
            'state' => 'Gujarat',
            'city' => 'Surat',
            'shape' => 'Princess',
            'size' => 0.900,
            'color' => 'F',
            'clarity' => 'VS1',
            'cut' => 'Excellent',
            'polish' => 'Very Good',
            'symmetry' => 'Excellent',
            'fluorescence_intensity' => 'None',
            'fluorescence_color' => 'None',
            'lab' => 'IGI',
            'report_no' => 'IGI48810239',
            'show_on_OM' => true,
            'report_date' => '2026-05-15',
            'status' => 'Rejected',
            'created_by' => 'Normal Admin'
        ]);

        // Sample Diamond 5 (Parcel)
        Diamond::create([
            'stock_no' => 'OM-P7702',
            'asking_price' => 1800.00,
            'asking_price_unit' => 'CT',
            'cash_price' => 8100.00,
            'cash_price_unit' => 'CT',
            'availability' => 'Available',
            'country' => 'India',
            'state' => 'Gujarat',
            'city' => 'Surat',
            'shape' => 'Round',
            'size' => 4.500,
            'color' => 'G',
            'clarity' => 'SI1',
            'is_parcel' => true,
            'number_of_diamonds' => 20,
            'lab' => 'None',
            'status' => 'Approved',
            'created_by' => 'Normal Admin'
        ]);

        // Sample Diamond 6 (Parcel)
        Diamond::create([
            'stock_no' => 'OM-P9910',
            'asking_price' => 2200.00,
            'asking_price_unit' => 'CT',
            'cash_price' => 15400.00,
            'cash_price_unit' => 'CT',
            'availability' => 'Available',
            'country' => 'India',
            'state' => 'Gujarat',
            'city' => 'Surat',
            'shape' => 'Pear',
            'size' => 7.000,
            'color' => 'H',
            'clarity' => 'VS2',
            'is_parcel' => true,
            'number_of_diamonds' => 15,
            'lab' => 'None',
            'status' => 'Approved',
            'created_by' => 'Super Admin'
        ]);

        // Additional Sample Diamonds to fill the table (Matching Screenshot 1)
        Diamond::create([
            'stock_no' => 'OM-111230',
            'asking_price' => 388.00,
            'asking_price_unit' => 'CT',
            'cash_price' => 321.00,
            'cash_price_unit' => 'CT',
            'availability' => 'Available',
            'country' => 'UK',
            'city' => 'London',
            'shape' => 'Round',
            'size' => 0.300,
            'color' => 'F',
            'clarity' => 'VS1',
            'cut' => 'Very Good',
            'polish' => 'Very Good',
            'symmetry' => 'Very Good',
            'fluorescence_intensity' => 'None',
            'lab' => 'CGL',
            'table_percent' => 58.00,
            'depth_percent' => 61.20,
            'length' => 4.20,
            'width' => 4.30,
            'depth' => 3.20,
            'status' => 'Approved',
            'created_by' => 'Shivani'
        ]);

        Diamond::create([
            'stock_no' => 'OM-111231',
            'asking_price' => 450.00,
            'asking_price_unit' => 'CT',
            'cash_price' => 380.00,
            'cash_price_unit' => 'CT',
            'availability' => 'Available',
            'country' => 'USA',
            'city' => 'New York',
            'shape' => 'Pear',
            'size' => 0.300,
            'color' => 'F',
            'clarity' => 'VS1',
            'cut' => 'Very Good',
            'polish' => 'Very Good',
            'symmetry' => 'Very Good',
            'fluorescence_intensity' => 'None',
            'lab' => 'CGL',
            'table_percent' => 58.00,
            'depth_percent' => 61.20,
            'length' => 4.20,
            'width' => 4.30,
            'depth' => 3.20,
            'status' => 'Approved',
            'created_by' => 'Nidhi'
        ]);

        Diamond::create([
            'stock_no' => 'OM-111232',
            'asking_price' => 500.00,
            'asking_price_unit' => 'CT',
            'cash_price' => 410.00,
            'cash_price_unit' => 'CT',
            'availability' => 'Available',
            'country' => 'UAE',
            'city' => 'Dubai',
            'shape' => 'Princess',
            'size' => 0.300,
            'color' => 'F',
            'clarity' => 'VS1',
            'cut' => 'Very Good',
            'polish' => 'Very Good',
            'symmetry' => 'Very Good',
            'fluorescence_intensity' => 'None',
            'lab' => 'CGL',
            'table_percent' => 58.00,
            'depth_percent' => 61.20,
            'length' => 4.20,
            'width' => 4.30,
            'depth' => 3.20,
            'status' => 'Approved',
            'created_by' => 'Vivan'
        ]);

        Diamond::create([
            'stock_no' => 'OM-111233',
            'asking_price' => 395.00,
            'asking_price_unit' => 'CT',
            'cash_price' => 315.00,
            'cash_price_unit' => 'CT',
            'availability' => 'Available',
            'country' => 'UK',
            'city' => 'London',
            'shape' => 'Oval',
            'size' => 0.300,
            'color' => 'F',
            'clarity' => 'VS1',
            'cut' => 'Very Good',
            'polish' => 'Very Good',
            'symmetry' => 'Very Good',
            'fluorescence_intensity' => 'None',
            'lab' => 'CGL',
            'table_percent' => 58.00,
            'depth_percent' => 61.20,
            'length' => 4.20,
            'width' => 4.30,
            'depth' => 3.20,
            'status' => 'Approved',
            'created_by' => 'Kirti'
        ]);

        Diamond::create([
            'stock_no' => 'OM-111234',
            'asking_price' => 410.00,
            'asking_price_unit' => 'CT',
            'cash_price' => 330.00,
            'cash_price_unit' => 'CT',
            'availability' => 'Available',
            'country' => 'India',
            'city' => 'Surat',
            'shape' => 'Round',
            'size' => 0.300,
            'color' => 'F',
            'clarity' => 'VS1',
            'cut' => 'Very Good',
            'polish' => 'Very Good',
            'symmetry' => 'Very Good',
            'fluorescence_intensity' => 'None',
            'lab' => 'CGL',
            'table_percent' => 58.00,
            'depth_percent' => 61.20,
            'length' => 4.20,
            'width' => 4.30,
            'depth' => 3.20,
            'status' => 'Approved',
            'created_by' => 'Varun'
        ]);

        Diamond::create([
            'stock_no' => 'OM-111235',
            'asking_price' => 420.00,
            'asking_price_unit' => 'CT',
            'cash_price' => 345.00,
            'cash_price_unit' => 'CT',
            'availability' => 'Available',
            'country' => 'Hongkong',
            'city' => 'Hongkong',
            'shape' => 'Pear',
            'size' => 0.300,
            'color' => 'F',
            'clarity' => 'VS1',
            'cut' => 'Very Good',
            'polish' => 'Very Good',
            'symmetry' => 'Very Good',
            'fluorescence_intensity' => 'None',
            'lab' => 'CGL',
            'table_percent' => 58.00,
            'depth_percent' => 61.20,
            'length' => 4.20,
            'width' => 4.30,
            'depth' => 3.20,
            'status' => 'Approved',
            'created_by' => 'Vishal'
        ]);
    }
}
