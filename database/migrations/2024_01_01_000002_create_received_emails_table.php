<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('received_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_email_id')->constrained()->onDelete('cascade');
            $table->string('message_id')->nullable()->unique();
            $table->string('from_address');
            $table->string('from_name')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->timestamp('received_at');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index('generated_email_id');
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('received_emails');
    }
};
