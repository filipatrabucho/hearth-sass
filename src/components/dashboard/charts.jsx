import styles from './charts.module.css'

// Gráficos simples em SVG puro — sem dependências extra para um dashboard
// "básico" (plano Free). Analytics avançados (retenção, heatmap) são Pro+.

export function LineChart({ data, height = 160 }) {
  if (!data || data.length === 0) {
    return <div className={styles.empty}>Sem dados suficientes ainda.</div>
  }

  const width = 600
  const padding = 24
  const max = Math.max(1, ...data.map((d) => d.value))
  const stepX = (width - padding * 2) / Math.max(1, data.length - 1)

  const points = data.map((d, i) => {
    const x = padding + i * stepX
    const y = height - padding - (d.value / max) * (height - padding * 2)
    return [x, y]
  })

  const linePath = points.map(([x, y], i) => `${i === 0 ? 'M' : 'L'}${x},${y}`).join(' ')
  const areaPath = `${linePath} L${points[points.length - 1][0]},${height - padding} L${points[0][0]},${height - padding} Z`

  return (
    <svg viewBox={`0 0 ${width} ${height}`} className={styles.svg} preserveAspectRatio="none">
      <path d={areaPath} fill="var(--purple)" opacity="0.12" />
      <path d={linePath} fill="none" stroke="var(--purple)" strokeWidth="2.5" />
      {points.map(([x, y], i) => (
        <circle key={i} cx={x} cy={y} r="3" fill="var(--purple)" />
      ))}
    </svg>
  )
}

export function BarChart({ data, height = 160 }) {
  if (!data || data.length === 0 || data.every((d) => d.value === 0)) {
    return <div className={styles.empty}>Sem dados suficientes ainda.</div>
  }

  const max = Math.max(1, ...data.map((d) => d.value))

  return (
    <div className={styles.barRow} style={{ height }}>
      {data.map((d) => (
        <div key={d.label} className={styles.barCol}>
          <div className={styles.barTrack}>
            <div
              className={styles.bar}
              style={{ height: `${(d.value / max) * 100}%`, background: d.color || 'var(--purple)' }}
            />
          </div>
          <span className={styles.barValue}>{d.value}</span>
          <span className={styles.barLabel}>{d.label}</span>
        </div>
      ))}
    </div>
  )
}
