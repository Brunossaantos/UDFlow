<?php

/**
 * tests/bootstrap.php
 *
 * Carrega autoload e .env pros testes - sem sessão/cookie, igual ao
 * bootstrap-cli.php (os testes usam o mesmo banco local de dev, não
 * um banco de teste separado; cada teste que grava dado abre uma
 * transação e dá rollback no tearDown, ver tests/PayloadBuilderTest.php).
 */

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

date_default_timezone_set('America/Sao_Paulo');
