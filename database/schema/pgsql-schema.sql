--
-- PostgreSQL database dump
--

\restrict LywFuGsxx4deufTNH44HSMrvsOy3MuHDgRsEbfSaPi56LlC7S7UwLJyMWCfOlnQ

-- Dumped from database version 18.4 (Ubuntu 18.4-1.pgdg24.04+1)
-- Dumped by pg_dump version 18.4 (Ubuntu 18.4-1.pgdg24.04+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: actividades; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.actividades (
    id_actividad bigint NOT NULL,
    id_proyecto bigint NOT NULL,
    numero integer NOT NULL,
    actividad character varying(255) NOT NULL,
    fecha_inicio date NOT NULL,
    fecha_fin date NOT NULL,
    duracion_dias integer,
    estado character varying(100) NOT NULL,
    porcentaje_cumplimiento_programado numeric(5,2) DEFAULT 0 NOT NULL,
    porcentaje_cumplimiento_real numeric(5,2) DEFAULT 0 NOT NULL,
    id_usuario_creador bigint,
    id_usuario_actualizador bigint,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    eliminado_en timestamp without time zone,
    id_actividad_predecesora bigint,
    id_componente bigint
);


--
-- Name: actividades_id_actividad_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.actividades ALTER COLUMN id_actividad ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.actividades_id_actividad_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: beneficiarios_proyecto; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.beneficiarios_proyecto (
    id_beneficiario_proyecto bigint NOT NULL,
    id_proyecto bigint NOT NULL,
    categoria character varying(100) NOT NULL,
    tipo character varying(150) NOT NULL,
    cantidad integer,
    descripcion text,
    id_usuario_creador bigint,
    id_usuario_actualizador bigint,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    eliminado_en timestamp without time zone
);


--
-- Name: beneficiarios_proyecto_id_beneficiario_proyecto_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.beneficiarios_proyecto ALTER COLUMN id_beneficiario_proyecto ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.beneficiarios_proyecto_id_beneficiario_proyecto_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: componentes_proyecto; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.componentes_proyecto (
    id_componente bigint NOT NULL,
    id_proyecto bigint NOT NULL,
    nombre character varying(150) NOT NULL,
    descripcion text,
    orden integer DEFAULT 0,
    id_usuario_creador bigint,
    id_usuario_actualizador bigint,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    eliminado_en timestamp without time zone
);


--
-- Name: componentes_proyecto_id_componente_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.componentes_proyecto ALTER COLUMN id_componente ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.componentes_proyecto_id_componente_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: contratos_proyecto; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contratos_proyecto (
    id_contrato bigint NOT NULL,
    id_proyecto bigint NOT NULL,
    id_componente bigint,
    numero integer NOT NULL,
    tipo_contrato character varying(150) NOT NULL,
    contratista character varying(255) NOT NULL,
    monto_vigente numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    anticipo numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    amortizacion_acumulada numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    monto_ejecutado_acumulado numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    liquido_pagable_acumulado numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    multas numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    retencion_gcc numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    total_descuentos numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    saldo_por_pagar numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    estado_contractual character varying(50) NOT NULL,
    fecha_conclusion_prevista date,
    fecha_entrega_provisional date,
    fecha_entrega_definitiva date,
    avance_fisico numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    avance_financiero numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    estado_fisico character varying(50),
    id_usuario_creador bigint,
    id_usuario_actualizador bigint,
    creado_en timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    actualizado_en timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    eliminado_en timestamp(0) without time zone,
    numero_minuta character varying(255),
    fecha_firma_contrato date,
    fecha_orden_proceder date,
    plazo_dias integer,
    archivo_orden_proceder_path character varying(255),
    archivo_orden_proceder_nombre_original character varying(255),
    anticipo_porcentaje numeric(5,2),
    activo boolean DEFAULT true NOT NULL
);


--
-- Name: contratos_proyecto_id_contrato_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contratos_proyecto_id_contrato_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contratos_proyecto_id_contrato_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contratos_proyecto_id_contrato_seq OWNED BY public.contratos_proyecto.id_contrato;


--
-- Name: decretos_supremos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.decretos_supremos (
    id_decreto_supremo bigint NOT NULL,
    numero_decreto character varying(255) NOT NULL,
    monto numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    descripcion text,
    fecha_decreto date,
    id_usuario_creador bigint,
    id_usuario_actualizador bigint,
    creado_en timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    actualizado_en timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    eliminado_en timestamp(0) without time zone
);


--
-- Name: decretos_supremos_id_decreto_supremo_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.decretos_supremos_id_decreto_supremo_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: decretos_supremos_id_decreto_supremo_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.decretos_supremos_id_decreto_supremo_seq OWNED BY public.decretos_supremos.id_decreto_supremo;


--
-- Name: decretos_supremos_proyecto; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.decretos_supremos_proyecto (
    id_decreto bigint NOT NULL,
    id_proyecto bigint NOT NULL,
    numero integer NOT NULL,
    numero_decreto character varying(255) NOT NULL,
    monto_inicial numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    incremento numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    monto_puesta_marcha_insumos numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    monto_auditoria_interna numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    id_usuario_creador bigint,
    id_usuario_actualizador bigint,
    creado_en timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    actualizado_en timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    eliminado_en timestamp(0) without time zone
);


--
-- Name: decretos_supremos_proyecto_id_decreto_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.decretos_supremos_proyecto_id_decreto_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: decretos_supremos_proyecto_id_decreto_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.decretos_supremos_proyecto_id_decreto_seq OWNED BY public.decretos_supremos_proyecto.id_decreto;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: historial_derecho_propietario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.historial_derecho_propietario (
    id_historial_derecho_propietario bigint CONSTRAINT historial_derecho_propietar_id_historial_derecho_propi_not_null NOT NULL,
    id_proyecto bigint NOT NULL,
    estado character varying(100) NOT NULL,
    descripcion text,
    fecha_evento date NOT NULL,
    observaciones text,
    id_usuario_creador bigint,
    id_usuario_actualizador bigint,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: historial_derecho_propietario_id_historial_derecho_propieta_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.historial_derecho_propietario ALTER COLUMN id_historial_derecho_propietario ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.historial_derecho_propietario_id_historial_derecho_propieta_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: modificaciones_contractuales; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.modificaciones_contractuales (
    id_modificacion bigint NOT NULL,
    id_contrato bigint NOT NULL,
    numero integer NOT NULL,
    tipo_modificacion character varying(150) NOT NULL,
    numero_documento_modificatorio character varying(150),
    cite_documento_aprobacion character varying(150),
    plazo_modificado_dias integer,
    monto_modificacion numeric(15,2),
    descripcion text,
    estado_registro_sicoes character varying(50),
    fecha_informe_aprobacion date,
    fecha_firma_documento date,
    estado_documento character varying(50),
    archivo_pdf_path character varying(255),
    archivo_pdf_nombre_original character varying(255),
    id_usuario_creador bigint,
    id_usuario_actualizador bigint,
    creado_en timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    actualizado_en timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    eliminado_en timestamp(0) without time zone,
    nueva_fecha_conclusion date,
    fecha_anterior date
);


--
-- Name: modificaciones_contractuales_id_modificacion_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.modificaciones_contractuales_id_modificacion_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: modificaciones_contractuales_id_modificacion_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.modificaciones_contractuales_id_modificacion_seq OWNED BY public.modificaciones_contractuales.id_modificacion;


--
-- Name: objetos_gasto_financiero; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.objetos_gasto_financiero (
    id_objeto bigint NOT NULL,
    id_partida bigint NOT NULL,
    numero integer NOT NULL,
    codigo_objeto character varying(50) NOT NULL,
    descripcion character varying(255) NOT NULL,
    monto_ene numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    monto_feb numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    monto_mar numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    monto_abr numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    monto_may numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    monto_jun numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    monto_jul numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    monto_ago numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    monto_sep numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    monto_oct numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    monto_nov numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    monto_dic numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    monto_ejecutado numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    id_usuario_creador bigint,
    id_usuario_actualizador bigint,
    creado_en timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    actualizado_en timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    eliminado_en timestamp(0) without time zone
);


--
-- Name: objetos_gasto_financiero_id_objeto_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.objetos_gasto_financiero_id_objeto_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: objetos_gasto_financiero_id_objeto_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.objetos_gasto_financiero_id_objeto_seq OWNED BY public.objetos_gasto_financiero.id_objeto;


--
-- Name: partidas_presupuestarias; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.partidas_presupuestarias (
    id_partida bigint NOT NULL,
    id_proyecto bigint NOT NULL,
    presupuesto_aprobado numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    id_usuario_creador bigint,
    id_usuario_actualizador bigint,
    creado_en timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    actualizado_en timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    eliminado_en timestamp(0) without time zone
);


--
-- Name: partidas_presupuestarias_id_partida_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.partidas_presupuestarias_id_partida_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: partidas_presupuestarias_id_partida_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.partidas_presupuestarias_id_partida_seq OWNED BY public.partidas_presupuestarias.id_partida;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: permisos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permisos (
    id_permiso bigint NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion character varying(255),
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: permisos_id_permiso_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.permisos ALTER COLUMN id_permiso ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.permisos_id_permiso_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: planillas_contrato; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.planillas_contrato (
    id_planilla bigint NOT NULL,
    id_contrato bigint NOT NULL,
    numero integer NOT NULL,
    periodo_desde date NOT NULL,
    periodo_hasta date NOT NULL,
    monto_certificado numeric(15,2) NOT NULL,
    dias_atraso integer DEFAULT 0 NOT NULL,
    avance_fisico numeric(5,2),
    amortizacion numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    retencion_gcc numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    multa numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    liquido_pagable numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    id_usuario_creador bigint,
    creado_en timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    importe_pagado_sigep numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    numero_c31 character varying(100),
    monto_c31 numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    fecha_aprobacion_fiscal date,
    fecha_elaboracion_planilla date,
    fecha_desembolso date
);


--
-- Name: planillas_contrato_id_planilla_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.planillas_contrato_id_planilla_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: planillas_contrato_id_planilla_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.planillas_contrato_id_planilla_seq OWNED BY public.planillas_contrato.id_planilla;


--
-- Name: problemas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.problemas (
    id_problema bigint NOT NULL,
    id_proyecto bigint NOT NULL,
    fecha_registro date NOT NULL,
    problema_identificado text NOT NULL,
    impacto character varying(50),
    solucion_propuesta text,
    responsable character varying(255),
    estado character varying(50),
    fecha_cierre date,
    id_usuario_creador bigint,
    id_usuario_actualizador bigint,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    eliminado_en timestamp without time zone,
    archivo_resolucion_path character varying(255),
    archivo_resolucion_nombre_original character varying(255)
);


--
-- Name: problemas_id_problema_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.problemas ALTER COLUMN id_problema ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.problemas_id_problema_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: productos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.productos (
    id_producto bigint NOT NULL,
    id_componente bigint NOT NULL,
    nombre character varying(255) NOT NULL,
    descripcion text,
    cantidad numeric(15,2),
    unidad character varying(50),
    orden integer DEFAULT 0,
    id_usuario_creador bigint,
    id_usuario_actualizador bigint,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    eliminado_en timestamp without time zone
);


--
-- Name: productos_id_producto_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.productos ALTER COLUMN id_producto ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.productos_id_producto_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: proyectos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.proyectos (
    id_proyecto bigint NOT NULL,
    codigo character varying(30) NOT NULL,
    nombre character varying(255) NOT NULL,
    fiscal_general character varying(255),
    fuente_financiamiento character varying(150),
    norma_financiador character varying(255),
    monto_decreto numeric(15,2),
    entidad_ejecutora character varying(150),
    componentes_lineas_descripcion text,
    fecha_inicio_contractual date,
    fecha_conclusion_inicial_contractual date,
    plazo_contractual_inicial_dias integer,
    fecha_conclusion_actual date,
    plazo_contractual_actual_dias integer,
    estado_actual_derecho_propietario text,
    id_usuario_creador bigint,
    id_usuario_actualizador bigint,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    eliminado_en timestamp without time zone,
    numero_sisin_web character varying(50),
    id_decreto_supremo bigint
);


--
-- Name: proyectos_id_proyecto_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.proyectos ALTER COLUMN id_proyecto ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.proyectos_id_proyecto_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: registros_auditoria; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.registros_auditoria (
    id_registro_auditoria bigint NOT NULL,
    id_usuario bigint,
    accion character varying(50) NOT NULL,
    tipo_registro character varying(150) NOT NULL,
    id_registro bigint NOT NULL,
    valores_anteriores jsonb,
    valores_nuevos jsonb,
    ruta character varying(255),
    metodo character varying(10),
    direccion_ip character varying(45),
    agente_usuario text,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: registros_auditoria_id_registro_auditoria_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.registros_auditoria ALTER COLUMN id_registro_auditoria ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.registros_auditoria_id_registro_auditoria_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: registros_inicio_sesion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.registros_inicio_sesion (
    id_registro_inicio_sesion bigint NOT NULL,
    id_usuario bigint,
    evento character varying(50) NOT NULL,
    inicio_exitoso boolean DEFAULT false NOT NULL,
    direccion_ip character varying(45),
    agente_usuario text,
    ocurrido_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: registros_inicio_sesion_id_registro_inicio_sesion_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.registros_inicio_sesion ALTER COLUMN id_registro_inicio_sesion ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.registros_inicio_sesion_id_registro_inicio_sesion_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: reprogramaciones_actividades; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reprogramaciones_actividades (
    id_reprogramacion bigint NOT NULL,
    id_actividad bigint NOT NULL,
    fecha_inicio_anterior date,
    fecha_fin_anterior date,
    fecha_inicio_nueva date,
    fecha_fin_nueva date,
    motivo text,
    estado_aprobacion character varying(50),
    id_usuario_aprobador bigint,
    aprobado_en timestamp without time zone,
    id_usuario_creador bigint,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: reprogramaciones_actividades_id_reprogramacion_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.reprogramaciones_actividades ALTER COLUMN id_reprogramacion ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.reprogramaciones_actividades_id_reprogramacion_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id_rol bigint NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion character varying(255),
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: roles_id_rol_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.roles ALTER COLUMN id_rol ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.roles_id_rol_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: roles_permisos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles_permisos (
    id_rol bigint NOT NULL,
    id_permiso bigint NOT NULL,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: ubicaciones_proyecto; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ubicaciones_proyecto (
    id_ubicacion_proyecto bigint NOT NULL,
    id_proyecto bigint NOT NULL,
    departamento character varying(100),
    provincia character varying(150),
    municipio character varying(150),
    comunidad_localidad character varying(150),
    coordenada_norte numeric(12,2),
    coordenada_este numeric(12,2),
    zona_utm character varying(20),
    id_usuario_creador bigint,
    id_usuario_actualizador bigint,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    eliminado_en timestamp without time zone
);


--
-- Name: ubicaciones_proyecto_id_ubicacion_proyecto_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.ubicaciones_proyecto ALTER COLUMN id_ubicacion_proyecto ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.ubicaciones_proyecto_id_ubicacion_proyecto_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: usuarios; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usuarios (
    id_usuario bigint NOT NULL,
    id_rol bigint NOT NULL,
    nombre character varying(150) NOT NULL,
    correo_electronico character varying(150) NOT NULL,
    contrasena character varying(255) NOT NULL,
    esta_activo boolean DEFAULT true NOT NULL,
    ultimo_inicio_sesion timestamp without time zone,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    eliminado_en timestamp without time zone
);


--
-- Name: usuarios_id_usuario_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.usuarios ALTER COLUMN id_usuario ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.usuarios_id_usuario_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: contratos_proyecto id_contrato; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contratos_proyecto ALTER COLUMN id_contrato SET DEFAULT nextval('public.contratos_proyecto_id_contrato_seq'::regclass);


--
-- Name: decretos_supremos id_decreto_supremo; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.decretos_supremos ALTER COLUMN id_decreto_supremo SET DEFAULT nextval('public.decretos_supremos_id_decreto_supremo_seq'::regclass);


--
-- Name: decretos_supremos_proyecto id_decreto; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.decretos_supremos_proyecto ALTER COLUMN id_decreto SET DEFAULT nextval('public.decretos_supremos_proyecto_id_decreto_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: modificaciones_contractuales id_modificacion; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modificaciones_contractuales ALTER COLUMN id_modificacion SET DEFAULT nextval('public.modificaciones_contractuales_id_modificacion_seq'::regclass);


--
-- Name: objetos_gasto_financiero id_objeto; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.objetos_gasto_financiero ALTER COLUMN id_objeto SET DEFAULT nextval('public.objetos_gasto_financiero_id_objeto_seq'::regclass);


--
-- Name: partidas_presupuestarias id_partida; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.partidas_presupuestarias ALTER COLUMN id_partida SET DEFAULT nextval('public.partidas_presupuestarias_id_partida_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: planillas_contrato id_planilla; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.planillas_contrato ALTER COLUMN id_planilla SET DEFAULT nextval('public.planillas_contrato_id_planilla_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: actividades actividades_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.actividades
    ADD CONSTRAINT actividades_pkey PRIMARY KEY (id_actividad);


--
-- Name: beneficiarios_proyecto beneficiarios_proyecto_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.beneficiarios_proyecto
    ADD CONSTRAINT beneficiarios_proyecto_pkey PRIMARY KEY (id_beneficiario_proyecto);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: componentes_proyecto componentes_proyecto_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.componentes_proyecto
    ADD CONSTRAINT componentes_proyecto_pkey PRIMARY KEY (id_componente);


--
-- Name: contratos_proyecto contratos_proyecto_id_proyecto_numero_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contratos_proyecto
    ADD CONSTRAINT contratos_proyecto_id_proyecto_numero_unique UNIQUE (id_proyecto, numero);


--
-- Name: contratos_proyecto contratos_proyecto_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contratos_proyecto
    ADD CONSTRAINT contratos_proyecto_pkey PRIMARY KEY (id_contrato);


--
-- Name: decretos_supremos decretos_supremos_numero_decreto_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.decretos_supremos
    ADD CONSTRAINT decretos_supremos_numero_decreto_unique UNIQUE (numero_decreto);


--
-- Name: decretos_supremos decretos_supremos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.decretos_supremos
    ADD CONSTRAINT decretos_supremos_pkey PRIMARY KEY (id_decreto_supremo);


--
-- Name: decretos_supremos_proyecto decretos_supremos_proyecto_id_proyecto_numero_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.decretos_supremos_proyecto
    ADD CONSTRAINT decretos_supremos_proyecto_id_proyecto_numero_unique UNIQUE (id_proyecto, numero);


--
-- Name: decretos_supremos_proyecto decretos_supremos_proyecto_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.decretos_supremos_proyecto
    ADD CONSTRAINT decretos_supremos_proyecto_pkey PRIMARY KEY (id_decreto);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: historial_derecho_propietario historial_derecho_propietario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.historial_derecho_propietario
    ADD CONSTRAINT historial_derecho_propietario_pkey PRIMARY KEY (id_historial_derecho_propietario);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: modificaciones_contractuales modificaciones_contractuales_id_contrato_numero_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modificaciones_contractuales
    ADD CONSTRAINT modificaciones_contractuales_id_contrato_numero_unique UNIQUE (id_contrato, numero);


--
-- Name: modificaciones_contractuales modificaciones_contractuales_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modificaciones_contractuales
    ADD CONSTRAINT modificaciones_contractuales_pkey PRIMARY KEY (id_modificacion);


--
-- Name: objetos_gasto_financiero objetos_gasto_financiero_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.objetos_gasto_financiero
    ADD CONSTRAINT objetos_gasto_financiero_pkey PRIMARY KEY (id_objeto);


--
-- Name: partidas_presupuestarias partidas_presupuestarias_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.partidas_presupuestarias
    ADD CONSTRAINT partidas_presupuestarias_pkey PRIMARY KEY (id_partida);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: permisos permisos_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permisos
    ADD CONSTRAINT permisos_nombre_key UNIQUE (nombre);


--
-- Name: permisos permisos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permisos
    ADD CONSTRAINT permisos_pkey PRIMARY KEY (id_permiso);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: planillas_contrato planillas_contrato_id_contrato_numero_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.planillas_contrato
    ADD CONSTRAINT planillas_contrato_id_contrato_numero_unique UNIQUE (id_contrato, numero);


--
-- Name: planillas_contrato planillas_contrato_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.planillas_contrato
    ADD CONSTRAINT planillas_contrato_pkey PRIMARY KEY (id_planilla);


--
-- Name: problemas problemas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.problemas
    ADD CONSTRAINT problemas_pkey PRIMARY KEY (id_problema);


--
-- Name: productos productos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT productos_pkey PRIMARY KEY (id_producto);


--
-- Name: proyectos proyectos_codigo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proyectos
    ADD CONSTRAINT proyectos_codigo_key UNIQUE (codigo);


--
-- Name: proyectos proyectos_numero_sisin_web_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proyectos
    ADD CONSTRAINT proyectos_numero_sisin_web_unique UNIQUE (numero_sisin_web);


--
-- Name: proyectos proyectos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proyectos
    ADD CONSTRAINT proyectos_pkey PRIMARY KEY (id_proyecto);


--
-- Name: registros_auditoria registros_auditoria_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registros_auditoria
    ADD CONSTRAINT registros_auditoria_pkey PRIMARY KEY (id_registro_auditoria);


--
-- Name: registros_inicio_sesion registros_inicio_sesion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registros_inicio_sesion
    ADD CONSTRAINT registros_inicio_sesion_pkey PRIMARY KEY (id_registro_inicio_sesion);


--
-- Name: reprogramaciones_actividades reprogramaciones_actividades_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reprogramaciones_actividades
    ADD CONSTRAINT reprogramaciones_actividades_pkey PRIMARY KEY (id_reprogramacion);


--
-- Name: roles roles_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_nombre_key UNIQUE (nombre);


--
-- Name: roles_permisos roles_permisos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles_permisos
    ADD CONSTRAINT roles_permisos_pkey PRIMARY KEY (id_rol, id_permiso);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id_rol);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: ubicaciones_proyecto ubicaciones_proyecto_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ubicaciones_proyecto
    ADD CONSTRAINT ubicaciones_proyecto_pkey PRIMARY KEY (id_ubicacion_proyecto);


--
-- Name: actividades uq_actividad_proyecto_numero; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.actividades
    ADD CONSTRAINT uq_actividad_proyecto_numero UNIQUE (id_proyecto, numero);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: usuarios usuarios_correo_electronico_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_correo_electronico_key UNIQUE (correo_electronico);


--
-- Name: usuarios usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_pkey PRIMARY KEY (id_usuario);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: contratos_proyecto contratos_proyecto_id_componente_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contratos_proyecto
    ADD CONSTRAINT contratos_proyecto_id_componente_foreign FOREIGN KEY (id_componente) REFERENCES public.componentes_proyecto(id_componente) ON DELETE SET NULL;


--
-- Name: contratos_proyecto contratos_proyecto_id_proyecto_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contratos_proyecto
    ADD CONSTRAINT contratos_proyecto_id_proyecto_foreign FOREIGN KEY (id_proyecto) REFERENCES public.proyectos(id_proyecto) ON DELETE CASCADE;


--
-- Name: contratos_proyecto contratos_proyecto_id_usuario_actualizador_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contratos_proyecto
    ADD CONSTRAINT contratos_proyecto_id_usuario_actualizador_foreign FOREIGN KEY (id_usuario_actualizador) REFERENCES public.usuarios(id_usuario) ON DELETE SET NULL;


--
-- Name: contratos_proyecto contratos_proyecto_id_usuario_creador_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contratos_proyecto
    ADD CONSTRAINT contratos_proyecto_id_usuario_creador_foreign FOREIGN KEY (id_usuario_creador) REFERENCES public.usuarios(id_usuario) ON DELETE SET NULL;


--
-- Name: decretos_supremos decretos_supremos_id_usuario_actualizador_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.decretos_supremos
    ADD CONSTRAINT decretos_supremos_id_usuario_actualizador_foreign FOREIGN KEY (id_usuario_actualizador) REFERENCES public.usuarios(id_usuario) ON DELETE SET NULL;


--
-- Name: decretos_supremos decretos_supremos_id_usuario_creador_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.decretos_supremos
    ADD CONSTRAINT decretos_supremos_id_usuario_creador_foreign FOREIGN KEY (id_usuario_creador) REFERENCES public.usuarios(id_usuario) ON DELETE SET NULL;


--
-- Name: decretos_supremos_proyecto decretos_supremos_proyecto_id_proyecto_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.decretos_supremos_proyecto
    ADD CONSTRAINT decretos_supremos_proyecto_id_proyecto_foreign FOREIGN KEY (id_proyecto) REFERENCES public.proyectos(id_proyecto) ON DELETE CASCADE;


--
-- Name: decretos_supremos_proyecto decretos_supremos_proyecto_id_usuario_actualizador_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.decretos_supremos_proyecto
    ADD CONSTRAINT decretos_supremos_proyecto_id_usuario_actualizador_foreign FOREIGN KEY (id_usuario_actualizador) REFERENCES public.usuarios(id_usuario) ON DELETE SET NULL;


--
-- Name: decretos_supremos_proyecto decretos_supremos_proyecto_id_usuario_creador_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.decretos_supremos_proyecto
    ADD CONSTRAINT decretos_supremos_proyecto_id_usuario_creador_foreign FOREIGN KEY (id_usuario_creador) REFERENCES public.usuarios(id_usuario) ON DELETE SET NULL;


--
-- Name: actividades fk_actividad_componente; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.actividades
    ADD CONSTRAINT fk_actividad_componente FOREIGN KEY (id_componente) REFERENCES public.componentes_proyecto(id_componente) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: actividades fk_actividad_predecesora; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.actividades
    ADD CONSTRAINT fk_actividad_predecesora FOREIGN KEY (id_actividad_predecesora) REFERENCES public.actividades(id_actividad) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: actividades fk_actividades_proyecto; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.actividades
    ADD CONSTRAINT fk_actividades_proyecto FOREIGN KEY (id_proyecto) REFERENCES public.proyectos(id_proyecto) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: actividades fk_actividades_usuario_actualizador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.actividades
    ADD CONSTRAINT fk_actividades_usuario_actualizador FOREIGN KEY (id_usuario_actualizador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: actividades fk_actividades_usuario_creador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.actividades
    ADD CONSTRAINT fk_actividades_usuario_creador FOREIGN KEY (id_usuario_creador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: registros_auditoria fk_auditoria_usuario; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registros_auditoria
    ADD CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: beneficiarios_proyecto fk_beneficiarios_proyecto; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.beneficiarios_proyecto
    ADD CONSTRAINT fk_beneficiarios_proyecto FOREIGN KEY (id_proyecto) REFERENCES public.proyectos(id_proyecto) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: beneficiarios_proyecto fk_beneficiarios_usuario_actualizador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.beneficiarios_proyecto
    ADD CONSTRAINT fk_beneficiarios_usuario_actualizador FOREIGN KEY (id_usuario_actualizador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: beneficiarios_proyecto fk_beneficiarios_usuario_creador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.beneficiarios_proyecto
    ADD CONSTRAINT fk_beneficiarios_usuario_creador FOREIGN KEY (id_usuario_creador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: componentes_proyecto fk_componentes_proyecto; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.componentes_proyecto
    ADD CONSTRAINT fk_componentes_proyecto FOREIGN KEY (id_proyecto) REFERENCES public.proyectos(id_proyecto) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: componentes_proyecto fk_componentes_usuario_actualizador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.componentes_proyecto
    ADD CONSTRAINT fk_componentes_usuario_actualizador FOREIGN KEY (id_usuario_actualizador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: componentes_proyecto fk_componentes_usuario_creador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.componentes_proyecto
    ADD CONSTRAINT fk_componentes_usuario_creador FOREIGN KEY (id_usuario_creador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: historial_derecho_propietario fk_historial_derecho_proyecto; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.historial_derecho_propietario
    ADD CONSTRAINT fk_historial_derecho_proyecto FOREIGN KEY (id_proyecto) REFERENCES public.proyectos(id_proyecto) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: historial_derecho_propietario fk_historial_derecho_usuario_actualizador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.historial_derecho_propietario
    ADD CONSTRAINT fk_historial_derecho_usuario_actualizador FOREIGN KEY (id_usuario_actualizador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: historial_derecho_propietario fk_historial_derecho_usuario_creador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.historial_derecho_propietario
    ADD CONSTRAINT fk_historial_derecho_usuario_creador FOREIGN KEY (id_usuario_creador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: problemas fk_problemas_proyecto; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.problemas
    ADD CONSTRAINT fk_problemas_proyecto FOREIGN KEY (id_proyecto) REFERENCES public.proyectos(id_proyecto) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: problemas fk_problemas_usuario_actualizador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.problemas
    ADD CONSTRAINT fk_problemas_usuario_actualizador FOREIGN KEY (id_usuario_actualizador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: problemas fk_problemas_usuario_creador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.problemas
    ADD CONSTRAINT fk_problemas_usuario_creador FOREIGN KEY (id_usuario_creador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: productos fk_productos_componente; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT fk_productos_componente FOREIGN KEY (id_componente) REFERENCES public.componentes_proyecto(id_componente) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: productos fk_productos_usuario_actualizador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT fk_productos_usuario_actualizador FOREIGN KEY (id_usuario_actualizador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: productos fk_productos_usuario_creador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT fk_productos_usuario_creador FOREIGN KEY (id_usuario_creador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: proyectos fk_proyectos_usuario_actualizador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proyectos
    ADD CONSTRAINT fk_proyectos_usuario_actualizador FOREIGN KEY (id_usuario_actualizador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: proyectos fk_proyectos_usuario_creador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proyectos
    ADD CONSTRAINT fk_proyectos_usuario_creador FOREIGN KEY (id_usuario_creador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: registros_inicio_sesion fk_registros_login_usuario; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registros_inicio_sesion
    ADD CONSTRAINT fk_registros_login_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: reprogramaciones_actividades fk_reprogramaciones_actividad; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reprogramaciones_actividades
    ADD CONSTRAINT fk_reprogramaciones_actividad FOREIGN KEY (id_actividad) REFERENCES public.actividades(id_actividad) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: reprogramaciones_actividades fk_reprogramaciones_usuario_aprobador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reprogramaciones_actividades
    ADD CONSTRAINT fk_reprogramaciones_usuario_aprobador FOREIGN KEY (id_usuario_aprobador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: reprogramaciones_actividades fk_reprogramaciones_usuario_creador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reprogramaciones_actividades
    ADD CONSTRAINT fk_reprogramaciones_usuario_creador FOREIGN KEY (id_usuario_creador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: roles_permisos fk_roles_permisos_permiso; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles_permisos
    ADD CONSTRAINT fk_roles_permisos_permiso FOREIGN KEY (id_permiso) REFERENCES public.permisos(id_permiso) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: roles_permisos fk_roles_permisos_rol; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles_permisos
    ADD CONSTRAINT fk_roles_permisos_rol FOREIGN KEY (id_rol) REFERENCES public.roles(id_rol) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: ubicaciones_proyecto fk_ubicaciones_proyecto; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ubicaciones_proyecto
    ADD CONSTRAINT fk_ubicaciones_proyecto FOREIGN KEY (id_proyecto) REFERENCES public.proyectos(id_proyecto) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: ubicaciones_proyecto fk_ubicaciones_usuario_actualizador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ubicaciones_proyecto
    ADD CONSTRAINT fk_ubicaciones_usuario_actualizador FOREIGN KEY (id_usuario_actualizador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: ubicaciones_proyecto fk_ubicaciones_usuario_creador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ubicaciones_proyecto
    ADD CONSTRAINT fk_ubicaciones_usuario_creador FOREIGN KEY (id_usuario_creador) REFERENCES public.usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: usuarios fk_usuarios_rol; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT fk_usuarios_rol FOREIGN KEY (id_rol) REFERENCES public.roles(id_rol) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: modificaciones_contractuales modificaciones_contractuales_id_contrato_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modificaciones_contractuales
    ADD CONSTRAINT modificaciones_contractuales_id_contrato_foreign FOREIGN KEY (id_contrato) REFERENCES public.contratos_proyecto(id_contrato) ON DELETE CASCADE;


--
-- Name: modificaciones_contractuales modificaciones_contractuales_id_usuario_actualizador_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modificaciones_contractuales
    ADD CONSTRAINT modificaciones_contractuales_id_usuario_actualizador_foreign FOREIGN KEY (id_usuario_actualizador) REFERENCES public.usuarios(id_usuario) ON DELETE SET NULL;


--
-- Name: modificaciones_contractuales modificaciones_contractuales_id_usuario_creador_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modificaciones_contractuales
    ADD CONSTRAINT modificaciones_contractuales_id_usuario_creador_foreign FOREIGN KEY (id_usuario_creador) REFERENCES public.usuarios(id_usuario) ON DELETE SET NULL;


--
-- Name: objetos_gasto_financiero objetos_gasto_financiero_id_partida_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.objetos_gasto_financiero
    ADD CONSTRAINT objetos_gasto_financiero_id_partida_foreign FOREIGN KEY (id_partida) REFERENCES public.partidas_presupuestarias(id_partida) ON DELETE CASCADE;


--
-- Name: objetos_gasto_financiero objetos_gasto_financiero_id_usuario_actualizador_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.objetos_gasto_financiero
    ADD CONSTRAINT objetos_gasto_financiero_id_usuario_actualizador_foreign FOREIGN KEY (id_usuario_actualizador) REFERENCES public.usuarios(id_usuario) ON DELETE SET NULL;


--
-- Name: objetos_gasto_financiero objetos_gasto_financiero_id_usuario_creador_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.objetos_gasto_financiero
    ADD CONSTRAINT objetos_gasto_financiero_id_usuario_creador_foreign FOREIGN KEY (id_usuario_creador) REFERENCES public.usuarios(id_usuario) ON DELETE SET NULL;


--
-- Name: partidas_presupuestarias partidas_presupuestarias_id_proyecto_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.partidas_presupuestarias
    ADD CONSTRAINT partidas_presupuestarias_id_proyecto_foreign FOREIGN KEY (id_proyecto) REFERENCES public.proyectos(id_proyecto) ON DELETE CASCADE;


--
-- Name: partidas_presupuestarias partidas_presupuestarias_id_usuario_actualizador_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.partidas_presupuestarias
    ADD CONSTRAINT partidas_presupuestarias_id_usuario_actualizador_foreign FOREIGN KEY (id_usuario_actualizador) REFERENCES public.usuarios(id_usuario) ON DELETE SET NULL;


--
-- Name: partidas_presupuestarias partidas_presupuestarias_id_usuario_creador_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.partidas_presupuestarias
    ADD CONSTRAINT partidas_presupuestarias_id_usuario_creador_foreign FOREIGN KEY (id_usuario_creador) REFERENCES public.usuarios(id_usuario) ON DELETE SET NULL;


--
-- Name: planillas_contrato planillas_contrato_id_contrato_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.planillas_contrato
    ADD CONSTRAINT planillas_contrato_id_contrato_foreign FOREIGN KEY (id_contrato) REFERENCES public.contratos_proyecto(id_contrato) ON DELETE CASCADE;


--
-- Name: planillas_contrato planillas_contrato_id_usuario_creador_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.planillas_contrato
    ADD CONSTRAINT planillas_contrato_id_usuario_creador_foreign FOREIGN KEY (id_usuario_creador) REFERENCES public.usuarios(id_usuario) ON DELETE SET NULL;


--
-- Name: proyectos proyectos_id_decreto_supremo_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proyectos
    ADD CONSTRAINT proyectos_id_decreto_supremo_foreign FOREIGN KEY (id_decreto_supremo) REFERENCES public.decretos_supremos(id_decreto_supremo) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

\unrestrict LywFuGsxx4deufTNH44HSMrvsOy3MuHDgRsEbfSaPi56LlC7S7UwLJyMWCfOlnQ

--
-- PostgreSQL database dump
--

\restrict qYVTPNEfFXoBx3K2pvpmrfA8Yu7b2KkGVbmSmjqIZ7K3rMXMEX72ujs0QFMx3iS

-- Dumped from database version 18.4 (Ubuntu 18.4-1.pgdg24.04+1)
-- Dumped by pg_dump version 18.4 (Ubuntu 18.4-1.pgdg24.04+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_08_21_022237_create_personal_access_tokens_table	1
5	2026_08_21_044014_add_numero_sisin_web_to_proyectos_table	2
6	2026_08_21_044124_add_numero_sisin_web_to_proyectos_table	3
7	2026_08_21_044218_add_numero_sisin_web_to_proyectos_table	4
8	2026_08_23_232343_create_contratos_proyecto_table	5
9	2026_08_24_054725_create_planillas_contrato_table	6
10	2026_08_31_141321_create_decretos_supremos_proyecto_table	7
11	2026_09_01_130835_add_datos_contractuales_to_contratos_proyecto_table	8
12	2026_09_01_151549_add_seguimiento_pago_to_planillas_contrato_table	9
13	2026_09_02_190702_create_modificaciones_contractuales_table	10
14	2026_09_02_195359_add_nueva_fecha_conclusion_to_modificaciones_contractuales_table	11
15	2026_09_03_133719_add_fecha_anterior_to_modificaciones_contractuales_table	12
16	2026_09_03_140136_add_archivo_orden_proceder_to_contratos_proyecto_table	13
17	2026_09_03_143504_add_anticipo_porcentaje_to_contratos_proyecto_table	14
18	2026_09_03_162354_add_archivo_resolucion_to_problemas_table	15
19	2026_09_03_192841_create_decretos_supremos_table	16
20	2026_09_03_192905_add_id_decreto_supremo_to_proyectos_table	16
21	2026_09_04_135034_add_activo_to_contratos_proyecto_table	17
22	2026_09_04_150944_create_partidas_presupuestarias_table	18
23	2026_09_04_151118_create_objetos_gasto_financiero_table	18
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 23, true);


--
-- PostgreSQL database dump complete
--

\unrestrict qYVTPNEfFXoBx3K2pvpmrfA8Yu7b2KkGVbmSmjqIZ7K3rMXMEX72ujs0QFMx3iS

