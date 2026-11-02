<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::create('portal_notifications',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained();$t->string('title');$t->string('url');$t->timestamp('read_at')->nullable();$t->timestamps();$t->index(['user_id','read_at']);});}public function down():void{Schema::dropIfExists('portal_notifications');}};