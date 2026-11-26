<?php

namespace App\Modules\Inventory\Support;

use Illuminate\Support\Facades\DB;

class BoxUid
{
    public static function next(): string
    {
        return DB::transaction(function () {
            if (! DB::getSchemaBuilder()->hasTable('box_uid_seq')) {
                DB::statement('CREATE TABLE IF NOT EXISTS box_uid_seq (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY) ENGINE=InnoDB');
            }
            do {
                DB::statement('INSERT INTO box_uid_seq () VALUES ()');
                $id = (int) DB::getPdo()->lastInsertId();
                $uid = str_pad((string) $id, 12, '0', STR_PAD_LEFT);
            } while (DB::table('boxes')->where('uid', $uid)->exists());

            return $uid;
        }, 3);
    }
}
