<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaitNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'waiting_no',
        'card_id',
        'is_cut_wait',
        'is_cut_done',
        'is_cut_call',
        'is_cut_now',
    ];

    protected $guarded = [
        'id'
    ];

    public static $rules = [
        'waiting_no' => ['required', 'integer'],
        'card_id' => ['required', 'integer'],
        'is_cut_wait' => ['required', 'boolean'],
        'is_cut_done' => ['required', 'boolean'],
        'is_cut_call' => ['required', 'boolean'],
        'is_cut_now' => ['required', 'boolean'],
    ];

}
