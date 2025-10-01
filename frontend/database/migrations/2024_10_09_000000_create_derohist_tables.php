<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('chain')) {
            Schema::create('chain', function (Blueprint $table) {
                $table->unsignedBigInteger('height')->primary();
                $table->unsignedInteger('depth')->nullable();
                $table->unsignedBigInteger('difficulty')->nullable();
                $table->char('hash', 64);
                $table->unsignedBigInteger('topoheight')->nullable();
                $table->smallInteger('major_version')->nullable();
                $table->smallInteger('minor_version')->nullable();
                $table->unsignedBigInteger('nonce')->nullable();
                $table->tinyInteger('orphan_status')->nullable();
                $table->tinyInteger('syncblock')->nullable();
                $table->tinyInteger('sideblock')->nullable();
                $table->unsignedInteger('txcount')->nullable();
                $table->decimal('reward', 20, 8)->nullable();
                $table->string('tips', 128)->nullable();
                $table->dateTime('timestamp');

                $table->index('timestamp', 'idx_chain_timestamp');
                $table->index('topoheight', 'idx_chain_topoheight');
            });
        }

        if (! Schema::hasTable('blockchain_transactions')) {
            Schema::create('blockchain_transactions', function (Blueprint $table) {
                $table->char('hash', 64)->primary();
                $table->unsignedBigInteger('height');
                $table->decimal('fees', 20, 8)->default(0);
                $table->tinyInteger('ignored')->nullable();
                $table->tinyInteger('in_pool')->nullable();
                $table->decimal('reward', 20, 8)->nullable();
                $table->string('sc_id', 128)->nullable();
                $table->string('signer', 128)->nullable();
                $table->string('txtype', 32)->nullable();
                $table->unsignedInteger('ring_size')->nullable();

                $table->index('height', 'idx_transactions_height');
                $table->foreign('height')->references('height')->on('chain')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('miners')) {
            Schema::create('miners', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('height');
                $table->string('address', 120);
                $table->unsignedInteger('miniblock')->default(0);
                $table->decimal('fees', 20, 8)->default(0);

                $table->index('height', 'idx_miners_height');
                $table->index('address', 'idx_miners_address');
                $table->foreign('height')->references('height')->on('chain')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('blockchain_tx_address')) {
            Schema::create('blockchain_tx_address', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('height');
                $table->string('address', 120);
                $table->char('hash', 64);

                $table->unique(['address', 'hash'], 'uq_tx_address');
                $table->index('height', 'idx_tx_addr_height');
                $table->index('address', 'idx_tx_addr_address');
                $table->foreign('height')->references('height')->on('chain')->cascadeOnDelete();
                $table->foreign('hash')->references('hash')->on('blockchain_transactions')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('deducted_transaction')) {
            Schema::create('deducted_transaction', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('height');
                $table->string('address', 120);

                $table->unique(['height', 'address'], 'uq_deducted');
                $table->index('height', 'idx_deducted_height');
                $table->foreign('height')->references('height')->on('chain')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('address_balance')) {
            Schema::create('address_balance', function (Blueprint $table) {
                $table->string('address', 120)->primary();
                $table->text('balance');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('address_balance');
        Schema::dropIfExists('deducted_transaction');
        Schema::dropIfExists('blockchain_tx_address');
        Schema::dropIfExists('miners');
        Schema::dropIfExists('blockchain_transactions');
        Schema::dropIfExists('chain');
    }
};
