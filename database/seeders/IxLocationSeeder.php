<?php

namespace Database\Seeders;

use App\Models\IxLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IxLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            ['name' => 'NoidaNetmagic', 'state' => 'Uttar Pradesh', 'node_type' => 'metro', 'switch' => '103.218.244.17', 'ports' => 48, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'New Delhi GK', 'state' => 'Delhi', 'node_type' => 'metro', 'switch' => '103.218.244.201', 'ports' => 48, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Mumbai GPX', 'state' => 'Maharashtra', 'node_type' => 'metro', 'switch' => '103.156.182.3', 'ports' => 48, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Mumbai Sify', 'state' => 'Maharashtra', 'node_type' => 'metro', 'switch' => '103.156.182.72', 'ports' => 12, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Chennai Sify', 'state' => 'Tamil Nadu', 'city' => 'Chennai', 'node_type' => 'metro', 'switch' => '103.218.247.2', 'ports' => 48, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Chennai STT', 'state' => 'Tamil Nadu', 'city' => 'Chennai', 'node_type' => 'metro', 'switch' => '103.218.247.1', 'ports' => 48, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Kolkata', 'state' => 'West Bengal', 'node_type' => 'metro', 'switch' => '103.156.183.125', 'ports' => 48, 'officer' => 'Jignesh Patel', 'zone' => 'East'],
            ['name' => 'Hyderabad', 'state' => 'Telangana', 'node_type' => 'metro', 'switch' => '103.156.182.190', 'ports' => 48, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Bangalore', 'state' => 'Karnataka', 'node_type' => 'metro', 'switch' => '103.156.183.170', 'ports' => 48, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Ahmedabad', 'state' => 'Gujarat', 'city' => 'Ahmedabad', 'node_type' => 'edge', 'switch' => '103.156.183.200', 'ports' => 48, 'officer' => 'Jignesh Patel', 'zone' => 'Gujarat'],
            ['name' => 'Guwahati', 'state' => 'Assam', 'city' => 'Guwahati', 'node_type' => 'edge', 'switch' => '103.156.183.150', 'ports' => 48, 'officer' => 'Jignesh Patel', 'zone' => 'East'],
            ['name' => 'Dehradun', 'state' => 'Uttarakhand', 'city' => 'Dehradun', 'node_type' => 'edge', 'switch' => '103.218.244.241', 'ports' => 12, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Kanpur', 'state' => 'Uttar Pradesh', 'node_type' => 'edge', 'switch' => '103.218.244.206', 'ports' => 12, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Prayagraj', 'state' => 'Uttar Pradesh', 'node_type' => 'edge', 'switch' => '103.218.244.209', 'ports' => 12, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Gorakhpur', 'state' => 'Uttar Pradesh', 'node_type' => 'edge', 'switch' => '103.218.244.205', 'ports' => 12, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Varanasi', 'state' => 'Uttar Pradesh', 'node_type' => 'edge', 'switch' => '103.218.244.208', 'ports' => 12, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Agra', 'state' => 'Uttar Pradesh', 'node_type' => 'edge', 'switch' => '103.218.244.204', 'ports' => 12, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Haldwani', 'state' => 'Uttarakhand', 'city' => 'Haldwani', 'node_type' => 'edge', 'switch' => '103.218.244.225', 'ports' => 48, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Meerut', 'state' => 'Uttar Pradesh', 'node_type' => 'edge', 'switch' => '103.218.244.203', 'ports' => 12, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Lucknow', 'state' => 'Uttar Pradesh', 'node_type' => 'edge', 'switch' => '103.218.244.207', 'ports' => 12, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Agartala', 'state' => 'Tripura', 'node_type' => 'edge', 'switch' => '103.156.182.101', 'ports' => 12, 'officer' => 'Jignesh Patel', 'zone' => 'East'],
            ['name' => 'Jaipur', 'state' => 'Rajasthan', 'node_type' => 'edge', 'switch' => '103.218.245.61', 'ports' => 12, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Rajkot', 'state' => 'Gujarat', 'city' => 'Rajkot', 'node_type' => 'edge', 'switch' => '103.156.183.202', 'ports' => 48, 'officer' => 'Jignesh Patel', 'zone' => 'Gujarat'],
            ['name' => 'Surat', 'state' => 'Gujarat', 'city' => 'Surat', 'node_type' => 'edge', 'switch' => '103.156.183.203', 'ports' => 48, 'officer' => 'Jignesh Patel', 'zone' => 'Gujarat'],
            ['name' => 'Vadodara', 'state' => 'Gujarat', 'city' => 'Vadodara', 'node_type' => 'edge', 'switch' => '103.156.183.201', 'ports' => 48, 'officer' => 'Jignesh Patel', 'zone' => 'Gujarat'],
            ['name' => 'Burdwan', 'state' => 'West Bengal', 'node_type' => 'edge', 'switch' => '103.156.183.115', 'ports' => 12, 'officer' => 'Jignesh Patel', 'zone' => 'East'],
            ['name' => 'Durgapur', 'state' => 'West Bengal', 'node_type' => 'edge', 'switch' => '103.156.183.144', 'ports' => 12, 'officer' => 'Jignesh Patel', 'zone' => 'East'],
            ['name' => 'Gurgaon', 'state' => 'Haryana', 'node_type' => 'edge', 'switch' => '103.218.244.213', 'ports' => 12, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Indore', 'state' => 'Madhya Pradesh', 'node_type' => 'edge', 'switch' => '103.156.183.231', 'ports' => 12, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Srinagar', 'state' => 'Jammu & Kashmir', 'node_type' => 'edge', 'switch' => '103.218.245.71', 'ports' => 12, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Jodhpur', 'state' => 'Rajasthan', 'node_type' => 'edge', 'switch' => '103.218.245.101', 'ports' => 12, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Bhopal', 'state' => 'Madhya Pradesh', 'node_type' => 'edge', 'switch' => '103.156.183.241', 'ports' => 12, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Pondicherry', 'state' => 'Tamil Nadu', 'node_type' => 'edge', 'switch' => '103.218.247.240', 'ports' => 12, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Mangalore', 'state' => 'Karnataka', 'node_type' => 'edge', 'switch' => '103.156.183.190', 'ports' => 12, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Panjim', 'state' => 'Goa', 'node_type' => 'edge', 'switch' => '103.156.183.160', 'ports' => 12, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Gwalior', 'state' => 'Madhya Pradesh', 'node_type' => 'edge', 'switch' => '103.218.244.211', 'ports' => 12, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Shimla', 'state' => 'Himachal Pradesh', 'node_type' => 'edge', 'switch' => '103.218.244.212', 'ports' => 12, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Triupati', 'state' => 'Andhra Pradesh', 'node_type' => 'edge', 'switch' => '103.218.247.249', 'ports' => 12, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Jammu', 'state' => 'Jammu & Kashmir', 'node_type' => 'edge', 'switch' => '103.218.244.215', 'ports' => 48, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Patna', 'state' => 'Bihar', 'node_type' => 'edge', 'switch' => '103.218.244.210', 'ports' => 48, 'officer' => 'Jignesh Patel', 'zone' => 'East'],
            ['name' => 'Coimbatore', 'state' => 'Tamil Nadu', 'node_type' => 'edge', 'switch' => '103.156.183.94', 'ports' => 48, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Nashik', 'state' => 'Maharashtra', 'node_type' => 'edge', 'switch' => '103.156.183.58', 'ports' => 12, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Cuttack', 'state' => 'Odisha', 'node_type' => 'edge', 'switch' => '103.218.247.253', 'ports' => 12, 'officer' => 'Jignesh Patel', 'zone' => 'East'],
            ['name' => 'Jabalpur', 'state' => 'Madhya Pradesh', 'node_type' => 'edge', 'switch' => '103.156.183.51', 'ports' => 12, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Raipur', 'state' => 'Chhattisgarh', 'node_type' => 'edge', 'switch' => '103.156.183.250', 'ports' => 12, 'officer' => 'Jignesh Patel', 'zone' => 'East'],
            ['name' => 'Nagpur', 'state' => 'Maharashtra', 'node_type' => 'edge', 'switch' => '103.156.183.65', 'ports' => 12, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Bhubaneswar', 'state' => 'Odisha', 'node_type' => 'edge', 'switch' => '103.218.247.254', 'ports' => 12, 'officer' => 'Jignesh Patel', 'zone' => 'East'],
            ['name' => 'Cochin', 'state' => 'Kerala', 'node_type' => 'edge', 'switch' => '103.156.183.30', 'ports' => 12, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Chandigarh', 'state' => 'Chandigarh', 'node_type' => 'edge', 'switch' => '103.218.244.221', 'ports' => 48, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Visakhapatnam (Vizag)', 'state' => 'Andhra Pradesh', 'node_type' => 'edge', 'switch' => '103.156.183.44', 'ports' => 12, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Thiruvananthapuram', 'state' => 'Kerala', 'node_type' => 'edge', 'switch' => '103.156.183.24', 'ports' => 12, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Ludhiana', 'state' => 'Punjab', 'node_type' => 'edge', 'switch' => '103.218.244.220', 'ports' => 12, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Ranchi', 'state' => 'Jharkhand', 'node_type' => 'edge', 'switch' => '103.218.244.218', 'ports' => 12, 'officer' => 'Jignesh Patel', 'zone' => 'East'],
            ['name' => 'Airoli Mumbai', 'state' => 'Maharashtra', 'node_type' => 'edge', 'switch' => '103.156.182.149', 'ports' => 12, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Jalandhar', 'state' => 'Punjab', 'node_type' => 'edge', 'switch' => '103.218.244.219', 'ports' => 12, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Vijayawada', 'state' => 'Andhra Pradesh', 'node_type' => 'edge', 'switch' => '103.156.183.37', 'ports' => 12, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Siliguri', 'state' => 'West Bengal', 'node_type' => 'edge', 'switch' => '103.156.183.18', 'ports' => 12, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Amritsar', 'state' => 'Punjab', 'node_type' => 'edge', 'switch' => '103.218.244.217', 'ports' => 12, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Madurai', 'state' => 'Tamil Nadu', 'node_type' => 'edge', 'switch' => '103.218.247.252', 'ports' => 12, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Salem', 'state' => 'Tamil Nadu', 'node_type' => 'edge', 'switch' => '103.218.247.248', 'ports' => 48, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Tuticorin', 'state' => 'Tamil Nadu', 'node_type' => 'edge', 'switch' => '103.218.247.185', 'ports' => 12, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Bareilly', 'state' => 'Uttar Pradesh', 'node_type' => 'edge', 'switch' => '103.218.244.216', 'ports' => 12, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Pune', 'state' => 'Maharashtra', 'node_type' => 'edge', 'switch' => '103.156.182.159', 'ports' => 48, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Thane', 'state' => 'Maharashtra', 'node_type' => 'edge', 'switch' => null, 'ports' => null, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Aurangabad', 'state' => 'Maharashtra', 'node_type' => 'edge', 'switch' => '103.156.182.157', 'ports' => 12, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Vasai', 'state' => 'Maharashtra', 'node_type' => 'edge', 'switch' => '103.156.182.152', 'ports' => 12, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Belgaum', 'state' => 'Karnataka', 'node_type' => 'edge', 'switch' => '103.156.182.154', 'ports' => 12, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Puri', 'state' => 'Odisha', 'node_type' => 'edge', 'switch' => '103.156.182.155', 'ports' => 12, 'officer' => 'Jignesh Patel', 'zone' => 'East'],
            ['name' => 'Hyderabad 2', 'state' => 'Telangana', 'node_type' => 'metro', 'switch' => '103.156.182.160', 'ports' => 48, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Mumbai3 (GPX-2)', 'state' => 'Maharashtra', 'node_type' => 'metro', 'switch' => '103.156.182.151', 'ports' => 48, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Bangalore2', 'state' => 'Karnataka', 'node_type' => 'metro', 'switch' => '103.156.183.189', 'ports' => 12, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'Sify Greenford Noida', 'state' => 'Uttar Pradesh', 'node_type' => 'metro', 'switch' => '103.218.244.5', 'ports' => 48, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Mumbai 4 (TATA)', 'state' => 'Maharashtra', 'node_type' => 'metro', 'switch' => '103.156.182.150', 'ports' => 48, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Chennai Santhome', 'state' => 'Tamil Nadu', 'city' => 'Chennai', 'node_type' => 'metro', 'switch' => '103.218.247.247', 'ports' => 48, 'officer' => 'Rajesh Kumar', 'zone' => 'South'],
            ['name' => 'CtrlS Noida', 'state' => 'Uttar Pradesh', 'node_type' => 'metro', 'switch' => '103.218.244.4', 'ports' => 48, 'officer' => 'Shashank Sharma', 'zone' => 'North'],
            ['name' => 'Ctrls Mumbai', 'state' => 'Maharashtra', 'node_type' => 'metro', 'switch' => '103.156.182.156', 'ports' => 48, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Mumbai Netmagic', 'state' => 'Maharashtra', 'node_type' => 'metro', 'switch' => null, 'ports' => 48, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
            ['name' => 'Solapur', 'state' => 'Maharashtra', 'node_type' => 'edge', 'switch' => '103.156.182.153', 'ports' => 12, 'officer' => 'Chirag Vasani', 'zone' => 'West'],
        ];

        foreach ($locations as $location) {
            IxLocation::updateOrCreate(
                ['slug' => Str::slug($location['name'])],
                [
                    'name' => $location['name'],
                    'state' => $location['state'],
                    'city' => $location['city'] ?? $location['name'],
                    'node_type' => $location['node_type'],
                    'switch_details' => $location['switch'],
                    'ports' => $location['ports'],
                    'nodal_officer' => $location['officer'],
                    'zone' => $location['zone'],
                    'metadata' => [
                        'node_type_label' => Str::title($location['node_type']).' Node',
                    ],
                    'is_active' => true,
                ]
            );
        }
    }
}
