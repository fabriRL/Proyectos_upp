/**
 * Composable con utilidades compartidas entre vistas.
 */
export function useUtils() {
  /** Clase DaisyUI para badge según estado */
  const badgeClass = (status) => {
    const map = {
      'Concluida':    'badge-success',
      'Concluido':    'badge-success',
      'Cerrado':      'badge-success', // ← agregado
      'Resuelto':     'badge-success',
      'En ejecución': 'badge-info',
      'En proceso':   'badge-info',
      'Reprogramada': 'badge-warning',
      'Vencido':      'badge-warning',
      'Alto':         'badge-warning',
      'Pendiente':    'badge-ghost',
      'Bajo':         'badge-ghost',
      'Suspendida':   'badge-error',
      'Cancelada':    'badge-error',
      'Paralizado':   'badge-error',
      'Crítico':      'badge-error',
      'CRÍTICO':      'badge-error',
    }
    return 'badge badge-sm ' + (map[status] || 'badge-ghost')
  }

  /** Clase de color para barra de progreso según valor */
  const fillClass = (v) => {
    if (v >= 75) return 'bg-success'
    if (v >= 45) return 'bg-primary'
    if (v >= 25) return 'bg-warning'
    return 'bg-error'
  }

  /** Clase de color de texto según valor de KPI */
  const kpiColor = (v) => {
    if (v >= 75) return 'text-success'
    if (v >= 45) return 'text-info'
    if (v >= 25) return 'text-warning'
    return 'text-error'
  }

  /** Formatea número como moneda boliviana */
  const fmtBs = (n) => 'Bs ' + Math.round(n).toLocaleString('es-BO')

  return { badgeClass, fillClass, kpiColor, fmtBs }
}
