/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts}'],
  theme: { extend: {} },
  plugins: [require('daisyui')],
  daisyui: {
    themes: [
      {
        emapa: {
          /* Primario — teal */
          "primary":           "#00c9a7",
          "primary-content":   "#061219",
          /* Secundario — navy medio */
          "secondary":         "#19354d",
          "secondary-content": "#8ea9bf",
          /* Acento — teal brillante */
          "accent":            "#00e5c0",
          "accent-content":    "#061219",
          /* Neutral — navy oscuro */
          "neutral":           "#122130",
          "neutral-content":   "#8ea9bf",
          /* Base — fondos principales */
          "base-100":          "#0d1f30",
          "base-200":          "#091520",
          "base-300":          "#152a3e",
          "base-content":      "#d0dde8",
          /* Semánticos */
          "info":              "#38bdf8",
          "info-content":      "#061219",
          "success":           "#00c9a7",
          "success-content":   "#061219",
          "warning":           "#f59e0b",
          "warning-content":   "#061219",
          "error":             "#f87171",
          "error-content":     "#ffffff",
        },
      },
    ],
    logs: false,
  },
}
