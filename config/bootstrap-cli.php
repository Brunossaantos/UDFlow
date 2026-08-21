<?php

/**
 * bootstrap-cli.php
 *
 * Versão enxuta do bootstrap.php pra scripts que rodam por linha de
 * comando (cron job) - não tem navegador nem cookie por trás, então
 * não faz sentido subir sessão aqui. Só carrega autoload, .env e
 * timezone mesmo.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NOME', 'DB_USUARIO', 'APP_PEPPER']);

date_default_timezone_set('America/Sao_Paulo');

$ambienteProducao = ($_ENV['APP_AMBIENTE'] ?? 'producao') === 'producao';
\Udflow\util\LogSistema::registrarManipuladoresGlobais($ambienteProducao);
