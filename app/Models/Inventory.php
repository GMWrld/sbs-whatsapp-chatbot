<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory'; // Specify the table name if different from the default convention
    
    protected $fillable = [
        'product_id',
        'branch_id',
        'stocks',
    ];

    protected $casts = [
        'product_id' => 'integer', // Cast 'product_id' to integer
        'branch_id' => 'integer',  // Cast 'branch_id' to integer
        'stocks' => 'integer',     // Cast 'stocks' to integer
    ];

    // Define relationships if needed
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
