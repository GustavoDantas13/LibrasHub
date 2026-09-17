<?php
declare(strict_types=1);

function ensureSocialSchema(PDO $pdo): void
{
    static $ready = false;
    if ($ready) return;

    $ensureIdentityAndIndexes = static function () use ($pdo): void {
        $missingHandle = $pdo->query("SELECT 1 FROM usuario WHERE social_handle IS NULL OR social_handle='' LIMIT 1");
        if ($missingHandle->fetchColumn()) $pdo->exec("UPDATE usuario SET social_handle=CONCAT('u',id_usuario) WHERE social_handle IS NULL OR social_handle=''");
        $index = $pdo->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='usuario' AND index_name='uq_usuario_social_handle'");
        $index->execute();
        if (!(bool)$index->fetchColumn()) $pdo->exec('ALTER TABLE usuario ADD UNIQUE INDEX uq_usuario_social_handle(social_handle)');
        $index = $pdo->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='social_posts' AND index_name='idx_social_posts_usuario_data'");
        $index->execute();
        if (!(bool)$index->fetchColumn()) $pdo->exec('ALTER TABLE social_posts ADD INDEX idx_social_posts_usuario_data(id_usuario,criado_em)');
        $index = $pdo->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='social_mensagens' AND index_name='idx_social_mensagens_resposta'");
        $index->execute();
        if (!(bool)$index->fetchColumn()) $pdo->exec('ALTER TABLE social_mensagens ADD INDEX idx_social_mensagens_resposta(id_mensagem_resposta)');
        $index = $pdo->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='social_post_comentarios' AND index_name='idx_social_comentarios_pai'");
        $index->execute();
        if (!(bool)$index->fetchColumn()) $pdo->exec('ALTER TABLE social_post_comentarios ADD INDEX idx_social_comentarios_pai(id_comentario_pai)');
    };

    $required = ['roles', 'usuario_roles', 'social_comunidades', 'social_comunidade_membros', 'social_posts', 'social_post_curtidas', 'social_post_comentarios', 'social_comentario_curtidas', 'social_chats', 'social_chat_membros', 'social_mensagens'];
    $missing = false;
    foreach ($required as $table) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
        $check->execute([$table]);
        if (!(bool)$check->fetchColumn()) {$missing = true; break;}
    }
    if (!$missing) {
        foreach ([['usuario','is_community_user'],['usuario','social_handle'],['usuario','foto_perfil'],['usuario','banner_perfil'],['usuario','status_visibilidade'],['usuario','ultimo_acesso_em'],['locais','latitude'],['locais','longitude'],['locais','visualizacoes'],['social_posts','midia_tipo'],['social_posts','editado_em'],['social_post_comentarios','id_comentario_pai'],['social_mensagens','editado_em'],['social_mensagens','id_mensagem_resposta']] as [$table,$column]) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?');
            $check->execute([$table,$column]);
            if (!(bool)$check->fetchColumn()) {$missing = true; break;}
        }
    }
    if (!$missing) {
        $check = $pdo->query("SELECT IS_NULLABLE FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='social_posts' AND column_name='id_comunidade'");
        if ($check->fetchColumn() !== 'YES') $missing = true;
    }
    if (!$missing) {
        $ensureIdentityAndIndexes();
        $ready = true;
        return;
    }

    $migration = dirname(__DIR__, 2).'/database/migrations/20260912_social.sql';
    if (!is_file($migration)) throw new RuntimeException('Migração do Social não encontrada.');
    $sql = (string)file_get_contents($migration);
    foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [])) as $statement) {
        $pdo->exec($statement);
    }
    $ensureIdentityAndIndexes();
    $ready = true;
}
