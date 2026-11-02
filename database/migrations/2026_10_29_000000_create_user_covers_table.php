<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Waterhole\Database\Migration;

return new class extends Migration {
    public function up()
    {
        Schema::create('user_covers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('position_x')->default(50);
            $table->unsignedTinyInteger('position_y')->default(50);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_covers');
    }
};
