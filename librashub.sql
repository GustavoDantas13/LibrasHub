use librashub;

create table usuario (
    id_usuario int auto_increment,
    nm_usuario varchar(255) not null,
    email_usuario varchar(255) not null,
    senha_usuario varchar(255) not null,
    tp_usuario varchar(50) not null,
    dt_usuario timestamp default current_timestamp,

    primary key (id_usuario),
    unique key (email_usuario)
);


create table lugar (
    id_lugar int auto_increment,
    id_usuario int not null,
    nm_lugar varchar(255) not null,	
    ds_endereco_lugar varchar(255) not null,
    ds_lugar varchar(255) not null,
    tp_local varchar(255) not null,

    primary key (id_lugar),

    constraint fk_lugar_usuario
        foreign key (id_usuario)
        references usuario(id_usuario)
        on delete cascade
        on update cascade
);


create table comunidade (
    id_comunidade int auto_increment,
    id_usuario int not null,
    nm_comunidade varchar(255) not null,
    ds_comunidade varchar(255),
    dt_criacao timestamp default current_timestamp,

    primary key (id_comunidade),

    constraint fk_comunidade_criador
        foreign key (id_usuario)
        references usuario(id_usuario)
        on delete cascade
        on update cascade
);


create table usuario_comunidade (
    id_usuario int not null,
    id_comunidade int not null,
    tp_cargo enum('criador', 'administrador', 'moderador', 'membro')
    default 'membro',
    dt_entrada datetime default current_timestamp,
    

    primary key (id_usuario, id_comunidade),

    constraint fk_usuario_comunidade_usuario
        foreign key (id_usuario)
        references usuario(id_usuario)
        on delete cascade
        on update cascade,

    constraint fk_usuario_comunidade_comunidade
        foreign key (id_comunidade)
        references comunidade(id_comunidade)
        on delete cascade
        on update cascade
);



create table post (
    id_post int auto_increment not null,
    id_usuario int not null,
    id_comunidade int not null,
    ds_post text not null,
    midia_url varchar(500),
    dt_post timestamp default current_timestamp,

    primary key (id_post),

    constraint fk_post_usuario
        foreign key (id_usuario)
        references usuario(id_usuario)
        on delete cascade
        on update cascade,

    constraint fk_post_comunidade
        foreign key (id_comunidade)
        references comunidade(id_comunidade)
        on delete cascade
        on update cascade
);


create table comentario (
    id_comentario int auto_increment not null,
    id_post int not null,
    id_usuario int not null,
    ds_comentario text not null,
    midia_url varchar(500),
    dt_comentario timestamp default current_timestamp,

    primary key (id_comentario),

    constraint fk_comentario_post
        foreign key (id_post)
        references post(id_post)
        on delete cascade
        on update cascade,

    constraint fk_comentario_usuario
        foreign key (id_usuario)
        references usuario(id_usuario)
        on delete cascade
        on update cascade
);


create  table gesto (
    id_gesto int auto_increment not null,
    nm_gest varchar(255) not null,
    id_administrador int not null,

	primary key (id_gesto),
    
    constraint fk_gesto_administrador
		foreign key (id_administrador)
        references usuario(id_usuario)
    
);


create table modelo_traducao (
	id_modelo int auto_increment,
    id_administrador int not null,
    dt_modelo timestamp default current_timestamp,
    labels varchar(500) not null,
    modelo_gesto varchar(500) not null,
    scaler_mean varchar(500) not null,
    scaler_scale varchar(500) not null,
    
    primary key (id_modelo),
    
    constraint fk_administrador_modelo
		foreign key (id_administrador)
		references usuario(id_usuario)
);


create table historico (
    id_historico int auto_increment not null,
    id_usuario int not null,
    id_gesto int not null,
    url_arquivo varchar(500),
    texto_resultado text,
    criado_em timestamp default current_timestamp,

    primary key (id_historico),

    constraint fk_historico_usuario
        foreign key (id_usuario)
        references usuario(id_usuario)
        on delete cascade
        on update cascade,

    constraint fk_historico_gesto
        foreign key (id_gesto)
        references gesto(id_gesto)
        on delete cascade
        on update cascade
);


create table chat (
    id_chat int auto_increment,
    tp_estado_chat enum('Permitido', 'Sileciado', 'Bloqueado')
    default 'Permitido',
	dt_chat timestamp default current_timestamp,
    
    primary key (id_chat)
);


create table usuario_chat (
    id_usuario int not null,
    id_chat int not null,

    primary key (id_usuario, id_chat),

    constraint fk_usuario_chat_usuario
        foreign key (id_usuario)
        references usuario(id_usuario)
        on delete cascade
        on update cascade,

    constraint fk_usuario_chat_chat
        foreign key (id_chat)
        references chat(id_chat)
        on delete cascade
        on update cascade
);


create table mensagem (
    id_mensagem int auto_increment,
    id_chat int not null,
    id_usuario int not null,
	ds_mensagem text not null,
    media_mensagem varchar(500),
    dt_mensagem timestamp default current_timestamp,

    primary key (id_mensagem),

    constraint fk_mensagem_chat
        foreign key (id_chat)
        references chat(id_chat)
		on delete cascade
        on update cascade,

    constraint fk_mensagem_usuario
        foreign key (id_usuario)
        references usuario(id_usuario)
        on delete cascade
        on update cascade
);