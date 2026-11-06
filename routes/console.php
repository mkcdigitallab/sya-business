<?php
use Illuminate\Support\Facades\Artisan;
Artisan::command('siya:about',function(){ $this->info('Siya Business — boutique locale'); })->purpose('Afficher les informations de la boutique');