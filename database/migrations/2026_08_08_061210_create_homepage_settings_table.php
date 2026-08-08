<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('homepage_settings', function (Blueprint $table) {
            $table->id();
            $table->string('hero_headline')->nullable();
            $table->text('hero_intro')->nullable();
            $table->string('hero_primary_cta_label')->nullable();
            $table->string('hero_primary_cta_url')->nullable();
            $table->string('hero_secondary_cta_label')->nullable();
            $table->string('hero_secondary_cta_url')->nullable();
            $table->string('quick_links_heading')->nullable();
            $table->string('programmes_heading')->nullable();
            $table->text('programmes_intro')->nullable();
            $table->string('news_heading')->nullable();
            $table->string('projects_heading')->nullable();
            $table->string('resources_heading')->nullable();
            $table->string('report_heading')->nullable();
            $table->text('report_intro')->nullable();
            $table->string('report_cta_label')->nullable();
            $table->string('report_cta_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_settings');
    }
};
