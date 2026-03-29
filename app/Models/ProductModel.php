<?php

/**
* app/Models/ProductModel.php
* This model represents the 'products' table in the database. It defines the fields that can be mass assigned and enables automatic timestamping for created_at and updated_at fields.
 */


namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table      = 'products';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'wc_id',
        'permalink',
        'title',
        'sku',
        'stock_quantity',
        'stock_status',
        'sale_price',
        'regular_price',
        'wc_created_at',
        'thumb_id',
        'cost',
    ];

    protected $useTimestamps = true;
}