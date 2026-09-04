// ─── Datos del proyecto principal ─────────────────────────────────────────
export const proyecto = {
  codigo: 'PRY-001',
  sisin: '0041-04174-00000',
  nombre: 'Implementación de la Industria de Cárnicos en el Beni',
  ejecutora: 'EMAPA',
  fiscal: 'Ing. Brigitte Rocío Camacho Vásquez',
  fuente: '92 - FINPRO',
  decreto: 'D.S. N° 4826 del 16 de noviembre de 2022',
  avanceFisico: 78.6,
  avanceFinanciero: 80.8,
  semaforo: 'CRÍTICO',
  diasRestantes: 144,
  plazoVigente: 1176,
  diasTranscurridos: 1032,
  montoDS: 294828023,
  montoContratos: 226147113,
}

// ─── Fases del flujo del sistema ──────────────────────────────────────────
export const phases = [
  { num: '01', name: 'Registro del proyecto',       icon: 'ti-plus-circle',   loop: false, steps: ['Crear proyecto', 'Datos generales', 'Vinculación D.S.', 'Beneficiarios / ubicación'] },
  { num: '02', name: 'Planificación',               icon: 'ti-calendar',      loop: false, steps: ['Contratos / paquetes', 'Orden de proceder', 'Cronograma (Gantt)', 'Prog. financiera SIGEP'] },
  { num: '03', name: 'Ejecución y seguimiento',     icon: 'ti-player-play',   loop: true,  steps: ['Avance semanal', 'Planillas de pago', 'Aprobación fiscal', 'Desembolso SIGEP'] },
  { num: '04', name: 'Modificaciones contractuales',icon: 'ti-pencil',        loop: false, steps: ['Orden de trabajo (OT)', 'Orden de cambio (OC)', 'Contrato modificatorio (CM)', 'Actualización plazos / montos'] },
  { num: '05', name: 'Gestión de problemas',        icon: 'ti-alert-triangle',loop: false, steps: ['Registro del problema', 'Clasificación por impacto', 'Asignación responsable', 'Cierre / resolución'] },
  { num: '06', name: 'Reporte y cierre',            icon: 'ti-check-circle',  loop: false, steps: ['Cálculo de KPIs', 'Semáforo del proyecto', 'Generación reporte V5', 'Cierre administrativo'] },
]

// ─── KPIs ─────────────────────────────────────────────────────────────────
export const kpis = [
  { label: 'Cumpl. cronograma',    v: 69.1 },
  { label: 'Gestión de problemas', v: 41.7 },
  { label: 'Índice del fiscal',    v: 44.2 },
  { label: 'Riesgo contractual',   v: 52.5 },
  { label: 'Cumpl. financiero',    v: 12.5 },
  { label: 'Utilización D.S.',     v: 79.6 },
]

// ─── Contratos ────────────────────────────────────────────────────────────
export const contratos = [
  { n: 1, tipo: 'Paquete I — Matadero frigorífico',     empresa: 'Empresa Morales Larrazabal M.L.',  monto: 50905840, af: 73.6, afin: 78.8, estado: 'Paralizado', short: 'Paq. I'   },
  { n: 2, tipo: 'Paquete II — Matadero frigorífico',    empresa: 'Asoc. Accidental Matadero Beni',   monto: 78138044, af: 100,  afin: 81.4, estado: 'Concluido', short: 'Paq. II'  },
  { n: 3, tipo: 'Vías y accesos',                       empresa: 'Vicstar Ingeniería S.R.L.',         monto: 13664335, af: 100,  afin: 64.5, estado: 'Concluido', short: 'Vías'     },
  { n: 4, tipo: 'Paquete III — Centro de confinamiento',empresa: 'Empresa Latigidconst SRL.',          monto: 40855317, af: 75.0, afin: 78.9, estado: 'Paralizado', short: 'Paq. III' },
  { n: 5, tipo: 'Paquete IV — Confinamiento secundario',empresa: 'Asoc. Accidental Yacaré',           monto: 34289413, af: 100,  afin: 94.7, estado: 'Concluido', short: 'Paq. IV'  },
  { n: 6, tipo: 'Supervisión general',                  empresa: 'FPS',                              monto: 8294161,  af: 62.6, afin: 70.0, estado: 'Vencido',   short: 'Superv.'  },
]

// ─── Actividades ──────────────────────────────────────────────────────────
export const actividades = [
  { n: 1,  nombre: 'Elaboración de TDR',      inicio: '01/03/26', fin: '19/03/26', d: 19, estado: 'Concluida',    p: 100, r: 100 },
  { n: 2,  nombre: 'Aprobación de TDR',       inicio: '20/03/26', fin: '30/03/26', d: 11, estado: 'Concluida',    p: 100, r: 100 },
  { n: 3,  nombre: 'Inscripción SIGEP',        inicio: '04/04/26', fin: '14/04/26', d: 11, estado: 'Concluida',    p: 100, r: 100 },
  { n: 4,  nombre: 'Modificación POA',         inicio: '17/05/26', fin: '07/06/26', d: 22, estado: 'Concluida',    p: 100, r: 100 },
  { n: 5,  nombre: 'Firma de contrato',        inicio: '12/06/26', fin: '11/09/26', d: 92, estado: 'Reprogramada', p: 93,  r: 40  },
  { n: 6,  nombre: 'Movimiento de tierras',    inicio: '28/06/26', fin: '18/08/26', d: 52, estado: 'En ejecución', p: 100, r: 75  },
  { n: 7,  nombre: 'Fundaciones',              inicio: '12/08/26', fin: '22/09/26', d: 41, estado: 'Pendiente',    p: 61,  r: 10  },
  { n: 8,  nombre: 'Montaje metálico',         inicio: '17/08/26', fin: '27/09/26', d: 41, estado: 'Suspendida',   p: 49,  r: 30  },
  { n: 9,  nombre: 'Cubierta',                 inicio: '29/09/26', fin: '23/10/26', d: 25, estado: 'Cancelada',    p: 100, r: 0   },
  { n: 10, nombre: 'Recepción provisional',    inicio: '01/12/26', fin: '15/12/26', d: 15, estado: 'Pendiente',    p: 0,   r: 0   },
  { n: 11, nombre: 'Recepción definitiva',     inicio: '01/01/27', fin: '30/01/27', d: 30, estado: 'Pendiente',    p: 0,   r: 0   },
  { n: 12, nombre: 'Cierre administrativo',    inicio: '31/01/27', fin: '19/02/27', d: 20, estado: 'Pendiente',    p: 0,   r: 0   },
]

// ─── Problemas ────────────────────────────────────────────────────────────
export const problemas = [
  { n: 1, fecha: '08/07/26', prob: 'Retraso en entrega de estructura metálica',                impacto: 'Bajo',    sol: 'Gestión con proveedor y reprogramación de actividades críticas',                                       resp: 'Fiscal General',           estado: 'Resuelto',   dias: 0  },
  { n: 2, fecha: '09/07/26', prob: 'Demora en aprobación de planillas de pago',                impacto: 'Alto',    sol: 'Coordinación con el área financiera para agilizar revisión y aprobación',                               resp: 'Área Financiera',          estado: 'En proceso', dias: 28 },
  { n: 3, fecha: '10/07/26', prob: 'Lluvias intensas en el área de cimentación',               impacto: 'Alto',    sol: 'Reprogramación de actividades y protección de excavaciones',                                           resp: 'Contratista',              estado: 'Pendiente',  dias: 27 },
  { n: 4, fecha: '21/06/26', prob: 'Falta de liberación de derecho propietario',               impacto: 'Crítico', sol: 'Gestión interinstitucional para suscripción de actas de conformidad y liberación de áreas',             resp: 'EMAPA / Gobierno Municipal', estado: 'En proceso', dias: 50 },
  { n: 5, fecha: '26/06/26', prob: 'Demora en importación de equipamiento electromecánico',    impacto: 'Crítico', sol: 'Seguimiento al despacho aduanero y coordinación con proveedor para priorizar envío',                   resp: 'Contratista',              estado: 'Pendiente',  dias: 45 },
]

// ─── Planillas de pago (Paquete I) ────────────────────────────────────────
export const planillas = [
  { n: 'Anticipo', imp: 10181168, mul: 0,     neto: 0,       amort: 0,      pag: 0,       dem: 108 },
  { n: 'N° 1',     imp: 1250390,  mul: 0,     neto: 1250390, amort: 250078, pag: 1000312, dem: 108 },
  { n: 'N° 2',     imp: 120812,   mul: 0,     neto: 120812,  amort: 24162,  pag: 96650,   dem: 108 },
  { n: 'N° 3',     imp: 1321126,  mul: 0,     neto: 1321126, amort: 264225, pag: 1056901, dem: 108 },
  { n: 'N° 4',     imp: 4173376,  mul: 0,     neto: 4173376, amort: 834675, pag: 3338701, dem: 108 },
  { n: 'N° 5',     imp: 1939250,  mul: 0,     neto: 1939250, amort: 387850, pag: 1551400, dem: 108 },
  { n: 'N° 6',     imp: 3074179,  mul: 15000, neto: 3074179, amort: 614835, pag: 2444343, dem: 108 },
  { n: 'N° 7',     imp: 9687648,  mul: 0,     neto: 9687648, amort: 1937529,pag: 7750118, dem: 108 },
  { n: 'N° 8',     imp: 1727642,  mul: 0,     neto: 1727642, amort: 345528, pag: 1382113, dem: 108 },
  { n: 'N° 9',     imp: 901165,   mul: 0,     neto: 901165,  amort: 180233, pag: 720932,  dem: 108 },
]

// ─── Programación financiera ───────────────────────────────────────────────
export const financiero = [
  { obj: '3.1.3',    desc: 'Productos agrícolas y pecuarios',    pres: 'Bs 69.8M', ejec: '—',        cumpl: '—',    estado: 'Pendiente' },
  { obj: '4.2.2.30', desc: 'Construcciones bienes públicos',     pres: '—',        ejec: 'Bs 1.1M',  cumpl: '12.5%',estado: 'Crítico'   },
  { obj: '4.2.2.40', desc: 'Supervisión de construcciones',      pres: '—',        ejec: '—',        cumpl: '—',    estado: 'Pendiente' },
]

export const meses = [0, 0, 0, 0, 0, 1128808, 7657182, 1818104, 19366456, 5600000, 3710000, 2432240]
export const mesLabels = ['E','F','M','A','M','J','J','A','S','O','N','D']

// ─── Listas de datos generales ─────────────────────────────────────────────
export const datosList = [
  ['Código del proyecto',      'PRY-001',                                                    true],
  ['Número SISIN Web',         '0041-04174-00000',                                           true],
  ['Nombre del proyecto',      'Implementación de la Industria de Cárnicos en el Beni',     false],
  ['Entidad ejecutora',        'EMAPA',                                                      false],
  ['Fiscal general',           'Ing. Brigitte Rocío Camacho Vásquez',                       false],
  ['Fuente de financiamiento', '92 - FINPRO',                                               false],
  ['Norma del financiador',    'D.S. N° 4826 del 16 de noviembre de 2022',                  false],
  ['Departamento',             'Beni',                                                       false],
  ['Provincia',                'General José Ballivián',                                     false],
  ['Municipio',                'San Borja; Reyes',                                           false],
  ['Comunidad / Localidad',    'Yucumo; Copaiba',                                           false],
]

export const beneList = [
  ['Familias productoras',           '2 741'],
  ['Total beneficiarios',            '665'],
  ['Empleos directos (construcción)', '150'],
  ['Empleos indirectos (construcción)','750'],
  ['Empleos directos (operación)',    '57'],
  ['Empleos indirectos (operación)',  '284'],
]

export const plazosList = [
  ['Inicio contractual',       '02/01/2024'],
  ['Conclusión inicial',       '18/11/2024'],
  ['Conclusión vigente',       '30/01/2027'],
  ['Plazo vigente (días)',     '1 176'],
  ['Días transcurridos',       '1 032'],
  ['Días restantes',           '144'],
]

// ─── Reporte ───────────────────────────────────────────────────────────────
export const repConfig = [
  ['Proyecto',              'PRY-001 — Implementación de la Industria de Cárnicos en el Beni'],
  ['Período de reporte',    'Julio 2026'],
  ['Formato de salida',     'Excel (.xlsx) — Plantilla V5'],
  ['Fecha de actualización','08/07/2026'],
]

export const secciones = [
  'Datos generales del proyecto',
  'Cronograma de actividades (Gantt)',
  'Avance físico y financiero general',
  'Resumen de indicadores (KPIs)',
  'Semáforo del proyecto',
  'Alertas activas',
  'Resumen de gestión por contrato',
  'Montos ejecutados vs. Decreto Supremo',
  'Programación financiera SIGEP',
  'Registro de problemas y soluciones',
  'Detalle de planillas por paquete',
  'Modificaciones contractuales',
]
