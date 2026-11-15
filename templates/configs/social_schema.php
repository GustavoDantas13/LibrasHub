<?php
declare(strict_types=1);

function ensureSocialSchema(PDO $pdo): void
{
    static $ready = false;
    if ($ready) return;

    $ensureEngagementSchema = static function () use ($pdo): void {
        foreach (['social_denuncias'] as $table) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
            $check->execute([$table]);
            if ((bool)$check->fetchColumn()) continue;
            $migration = dirname(__DIR__, 2).'/database/migrations/20260918_engagement_moderation.sql';
            if (!is_file($migration)) throw new RuntimeException('Migração de engajamento e denúncias não encontrada.');
            foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', (string)file_get_contents($migration)) ?: [])) as $statement) $pdo->exec($statement);
            break;
        }
    };

    $ensureReportModerationSchema = static function () use ($pdo): void {
        $columns = [
            'alvo_titulo' => "VARCHAR(255) NULL AFTER detalhes",
            'alvo_resumo' => "VARCHAR(1000) NULL AFTER alvo_titulo",
            'origem_path' => "VARCHAR(500) NULL AFTER alvo_resumo",
            'id_moderador' => "INT NULL AFTER status",
            'resolucao' => "VARCHAR(1000) NULL AFTER id_moderador",
            'resolvido_em' => "TIMESTAMP NULL DEFAULT NULL AFTER resolucao",
        ];
        foreach ($columns as $column => $definition) {
            $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='social_denuncias' AND column_name=?");
            $check->execute([$column]);
            if (!(bool)$check->fetchColumn()) $pdo->exec("ALTER TABLE social_denuncias ADD COLUMN {$column} {$definition}");
        }
        $index = $pdo->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='social_denuncias' AND index_name='idx_denuncias_moderador'");
        $index->execute();
        if (!(bool)$index->fetchColumn()) $pdo->exec('ALTER TABLE social_denuncias ADD INDEX idx_denuncias_moderador(id_moderador,atualizado_em)');
        $enum = (string)$pdo->query("SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='social_denuncias' AND column_name='tipo_alvo'")->fetchColumn();
        if ($enum !== '' && !str_contains($enum, "'local'")) $pdo->exec("ALTER TABLE social_denuncias MODIFY COLUMN tipo_alvo ENUM('post','comentario','usuario','comunidade','local') NOT NULL");
    };

    $ensurePunishmentSchema = static function () use ($pdo): void {
        $check = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='usuario_punicoes'");
        if ((bool)$check->fetchColumn()) return;
        $pdo->exec("CREATE TABLE usuario_punicoes (
            id_punicao BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,id_administrador INT NOT NULL,
            tipo ENUM('publicar','cadastrar_locais') NOT NULL,motivo VARCHAR(500) NOT NULL,
            inicio_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,expira_em DATETIME NOT NULL,revogada_em DATETIME NULL DEFAULT NULL,
            KEY idx_usuario_punicoes_ativas(id_usuario,tipo,expira_em,revogada_em),
            KEY idx_usuario_punicoes_admin(id_administrador,inicio_em),
            CONSTRAINT fk_usuario_punicoes_usuario FOREIGN KEY(id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
            CONSTRAINT fk_usuario_punicoes_admin FOREIGN KEY(id_administrador) REFERENCES usuario(id_usuario) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    };

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

    $followersCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='social_seguidores'");
    $followersCheck->execute();
    if (!(bool)$followersCheck->fetchColumn()) {
        $followersMigration = dirname(__DIR__, 2).'/database/migrations/20260917_followers.sql';
        if (!is_file($followersMigration)) throw new RuntimeException('Migração de seguidores não encontrada.');
        foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', (string)file_get_contents($followersMigration)) ?: [])) as $statement) $pdo->exec($statement);
    }

    $required = ['roles', 'usuario_roles', 'social_seguidores', 'social_comunidades', 'social_comunidade_membros', 'social_posts', 'social_post_curtidas', 'social_post_comentarios', 'social_comentario_curtidas', 'social_chats', 'social_chat_membros', 'social_mensagens'];
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
        $ensureEngagementSchema();
        $ensureReportModerationSchema();
        $ensurePunishmentSchema();
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
    $ensureEngagementSchema();
    $ensureReportModerationSchema();
    $ensurePunishmentSchema();
    $ready = true;
}
