<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDiperbaruiPadaToTbNotifikasi extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tb_notifikasi')) {
            return;
        }

        if ($this->db->fieldExists('diperbarui_pada', 'tb_notifikasi')) {
            return;
        }

        $this->forge->addColumn('tb_notifikasi', [
            'diperbarui_pada' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'dibuat_pada',
            ],
        ]);
    }

    public function down()
    {
        if (! $this->db->tableExists('tb_notifikasi')) {
            return;
        }

        if (! $this->db->fieldExists('diperbarui_pada', 'tb_notifikasi')) {
            return;
        }

        $this->forge->dropColumn('tb_notifikasi', 'diperbarui_pada');
    }
}
