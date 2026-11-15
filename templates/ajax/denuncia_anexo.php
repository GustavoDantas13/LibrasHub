<?php
/** Visualização autenticada de evidências privadas - somente admins. */
declare(strict_types=1);
session_start();
if(empty($_SESSION['usuario_id'])){http_response_code(401);exit;}
require_once __DIR__.'/../configs/config.php';
$s=$pdo->prepare('SELECT tp_usuario FROM usuario WHERE id_usuario=? LIMIT 1');$s->execute([(int)$_SESSION['usuario_id']]);
if(mb_strtolower(trim((string)$s->fetchColumn()))!=='administrador'){http_response_code(403);exit;}
$id=max(0,(int)($_GET['id']??0));if(!$id){http_response_code(400);exit;}
$q=$pdo->prepare('SELECT ds_caminho AS caminho,nm_arquivo_original AS nome_original FROM mobile_denuncia_anexos WHERE id_anexo=?');$q->execute([$id]);$file=$q->fetch(PDO::FETCH_ASSOC);
if(!$file){http_response_code(404);exit;}
$root=realpath(dirname(__DIR__,4).'/librashub-private');
$path=$root!==false?realpath($root.'/'.$file['caminho']):false;
if(!$root||!$path||!str_starts_with($path,$root.DIRECTORY_SEPARATOR)||!is_file($path)){http_response_code(404);exit;}
$type=(new finfo(FILEINFO_MIME_TYPE))->file($path);
header('Content-Type: '.$type);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="evidencia-'.(int)$id.'.bin"');
header('Cache-Control: private, no-store');
readfile($path);
