// Deriva a paleta toda (fundo, cartões, bordas, hover) a partir das 3 cores
// que o workspace configura no dashboard: fundo, destaque e texto.
// Isto é o que permite ao Hearth "adaptar-se à identidade do cliente sem
// impor uma palette fixa" (ver roadmap: Temas e identidade visual).

function hexToRgb(hex) {
  const clean = hex.replace('#', '')
  const full = clean.length === 3 ? clean.split('').map((c) => c + c).join('') : clean
  const num = parseInt(full, 16)
  return { r: (num >> 16) & 255, g: (num >> 8) & 255, b: num & 255 }
}

function rgbToHex({ r, g, b }) {
  const toHex = (v) => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')
  return `#${toHex(r)}${toHex(g)}${toHex(b)}`
}

function mix(hexA, hexB, weight) {
  const a = hexToRgb(hexA)
  const b = hexToRgb(hexB)
  return rgbToHex({
    r: a.r + (b.r - a.r) * weight,
    g: a.g + (b.g - a.g) * weight,
    b: a.b + (b.b - a.b) * weight,
  })
}

function relativeLuminance(hex) {
  const { r, g, b } = hexToRgb(hex)
  const [rs, gs, bs] = [r, g, b].map((v) => v / 255)
  return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs
}

export const DEFAULT_THEME = {
  theme_bg: '#080B0F',
  theme_accent: '#7B61FF',
  theme_text: '#F5F3EF',
}

export function applyWorkspaceTheme(workspace) {
  const bg = workspace?.theme_bg || DEFAULT_THEME.theme_bg
  const accent = workspace?.theme_accent || DEFAULT_THEME.theme_accent
  const text = workspace?.theme_text || DEFAULT_THEME.theme_text

  const isDark = relativeLuminance(bg) < 0.5
  // Em fundo escuro, cartões ficam mais claros que o fundo; em fundo claro, mais escuros.
  const lift = isDark ? '#FFFFFF' : '#000000'

  const root = document.documentElement.style
  root.setProperty('--black', bg)
  root.setProperty('--surface', mix(bg, lift, 0.04))
  root.setProperty('--card', mix(bg, lift, 0.07))
  root.setProperty('--card-hover', mix(bg, lift, 0.1))
  root.setProperty('--border', isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.08)')
  root.setProperty('--border-md', isDark ? 'rgba(255,255,255,0.13)' : 'rgba(0,0,0,0.14)')
  root.setProperty('--white', text)
  root.setProperty('--text-mid', mix(text, bg, 0.28))
  root.setProperty('--text-muted', mix(text, bg, 0.58))
  root.setProperty('--purple', accent)
  root.setProperty('--purple-dk', mix(accent, '#000000', 0.18))
}
