/**
 * Konfigurasi Tailwind SIPERSU FT-UMB.
 * Gabungan token dari seluruh layar Google Stitch (palet Material 3, palet "umb"
 * pada layar login/verifikasi) dan stich/sipersu_ft_umb_design_system/DESIGN.md.
 * CSS dikompilasi SEKALI (npm run build) ke public/css/app.css; Node.js tidak
 * diperlukan saat aplikasi berjalan.
 */
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    './resources/views/**/*.blade.php',
    './app/**/*.php',
    './resources/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        background: '#faf8ff',
        error: '#ba1a1a',
        'error-container': '#ffdad6',
        'inverse-on-surface': '#eef0ff',
        'inverse-primary': '#85d8a1',
        'inverse-surface': '#283044',
        'on-background': '#131b2e',
        'on-error': '#ffffff',
        'on-error-container': '#93000a',
        'on-primary': '#ffffff',
        'on-primary-container': '#95e9b0',
        'on-primary-fixed': '#00210f',
        'on-primary-fixed-variant': '#00522d',
        'on-secondary': '#ffffff',
        'on-secondary-container': '#6c5000',
        'on-secondary-fixed': '#251a00',
        'on-secondary-fixed-variant': '#5b4300',
        'on-surface': '#131b2e',
        'on-surface-variant': '#3f4941',
        'on-tertiary': '#ffffff',
        'on-tertiary-container': '#87ecaa',
        'on-tertiary-fixed': '#00210f',
        'on-tertiary-fixed-variant': '#00522d',
        outline: '#6f7a70',
        'outline-variant': '#bfc9be',
        primary: '#00512c',
        'primary-container': '#0f6b3e',
        'primary-fixed': '#a1f5bb',
        'primary-fixed-dim': '#85d8a1',
        secondary: '#785900',
        'secondary-container': '#fcc019',
        'secondary-fixed': '#ffdf9d',
        'secondary-fixed-dim': '#f9bd14',
        surface: '#faf8ff',
        'surface-bright': '#faf8ff',
        'surface-container': '#eaedff',
        'surface-container-high': '#e2e7ff',
        'surface-container-highest': '#dae2fd',
        'surface-container-low': '#f2f3ff',
        'surface-container-lowest': '#ffffff',
        'surface-dim': '#d2d9f4',
        'surface-tint': '#116c3f',
        'surface-variant': '#dae2fd',
        tertiary: '#00512c',
        'tertiary-container': '#006c3d',
        'tertiary-fixed': '#92f8b5',
        'tertiary-fixed-dim': '#76db9b',
        umb: {
          green: '#0F6B3E',
          'green-dark': '#0a4d2c',
          'green-light': '#14864e',
          'green-subtle': '#E8F5ED',
          gold: '#F2B705',
          'gold-light': '#FFD036'
        },
        status: {
          diajukan: {
            bg: '#F1F5F9',
            text: '#475569',
            border: '#CBD5E1'
          },
          diverifikasi: {
            bg: '#EEF2FF',
            text: '#4338CA',
            border: '#C7D2FE'
          },
          disetujui: {
            bg: '#FEF3C7',
            text: '#B45309',
            border: '#FDE68A'
          },
          ditandatangani: {
            bg: '#F3E8FF',
            text: '#6B21A8',
            border: '#E9D5FF'
          },
          selesai: {
            bg: '#ECFDF5',
            text: '#065F46',
            border: '#A7F3D0'
          },
          ditolak: {
            bg: '#FFE4E6',
            text: '#9F1239',
            border: '#FECDD3'
          }
        }
      },
      borderRadius: {
        DEFAULT: '0.25rem',
        lg: '0.5rem',
        xl: '0.75rem',
        full: '9999px'
      },
      spacing: {
        gutter: '1.5rem',
        'space-lg': '1.5rem',
        'gutter-mobile': '0.75rem',
        'space-md': '1rem',
        'space-xl': '2rem',
        'space-xs': '0.25rem',
        'space-sm': '0.5rem',
        'margin-mobile': '1rem',
        margin: '2rem'
      },
      fontFamily: {
        jakarta: [
          '"Plus Jakarta Sans"',
          'ui-sans-serif',
          'system-ui',
          'sans-serif'
        ],
        'label-sm': [
          'Inter',
          'ui-sans-serif',
          'system-ui',
          'sans-serif'
        ],
        'label-md': [
          'Inter',
          'ui-sans-serif',
          'system-ui',
          'sans-serif'
        ],
        'label-lg': [
          'Inter',
          'ui-sans-serif',
          'system-ui',
          'sans-serif'
        ],
        'headline-md': [
          '"Plus Jakarta Sans"',
          'ui-sans-serif',
          'system-ui',
          'sans-serif'
        ],
        'body-lg': [
          'Inter',
          'ui-sans-serif',
          'system-ui',
          'sans-serif'
        ],
        'headline-xl': [
          '"Plus Jakarta Sans"',
          'ui-sans-serif',
          'system-ui',
          'sans-serif'
        ],
        'headline-xl-mobile': [
          '"Plus Jakarta Sans"',
          'ui-sans-serif',
          'system-ui',
          'sans-serif'
        ],
        'body-sm': [
          'Inter',
          'ui-sans-serif',
          'system-ui',
          'sans-serif'
        ],
        'body-md': [
          'Inter',
          'ui-sans-serif',
          'system-ui',
          'sans-serif'
        ],
        'headline-lg': [
          '"Plus Jakarta Sans"',
          'ui-sans-serif',
          'system-ui',
          'sans-serif'
        ],
        'headline-sm': [
          '"Plus Jakarta Sans"',
          'ui-sans-serif',
          'system-ui',
          'sans-serif'
        ],
        'mono-data': [
          'Inter',
          'ui-sans-serif',
          'system-ui',
          'sans-serif'
        ],
        'headline-lg-mobile': [
          '"Plus Jakarta Sans"',
          'ui-sans-serif',
          'system-ui',
          'sans-serif'
        ]
      },
      fontSize: {
        'label-sm': [
          '11px',
          {
            lineHeight: '14px',
            letterSpacing: '0.04em',
            fontWeight: '600'
          }
        ],
        'label-md': [
          '12px',
          {
            lineHeight: '16px',
            letterSpacing: '0.02em',
            fontWeight: '600'
          }
        ],
        'label-lg': [
          '14px',
          {
            lineHeight: '20px',
            fontWeight: '600'
          }
        ],
        'headline-md': [
          '18px',
          {
            lineHeight: '26px',
            letterSpacing: '-0.01em',
            fontWeight: '600'
          }
        ],
        'body-lg': [
          '16px',
          {
            lineHeight: '26px',
            fontWeight: '400'
          }
        ],
        'headline-xl': [
          '30px',
          {
            lineHeight: '38px',
            letterSpacing: '-0.02em',
            fontWeight: '700'
          }
        ],
        'headline-xl-mobile': [
          '24px',
          {
            lineHeight: '32px',
            letterSpacing: '-0.02em',
            fontWeight: '700'
          }
        ],
        'body-sm': [
          '13px',
          {
            lineHeight: '18px',
            fontWeight: '400'
          }
        ],
        'body-md': [
          '14px',
          {
            lineHeight: '22px',
            fontWeight: '400'
          }
        ],
        'headline-lg': [
          '24px',
          {
            lineHeight: '32px',
            letterSpacing: '-0.015em',
            fontWeight: '600'
          }
        ],
        'headline-sm': [
          '16px',
          {
            lineHeight: '24px',
            fontWeight: '600'
          }
        ],
        'mono-data': [
          '13px',
          {
            lineHeight: '18px',
            fontWeight: '500'
          }
        ],
        'headline-lg-mobile': [
          '20px',
          {
            lineHeight: '28px',
            letterSpacing: '-0.01em',
            fontWeight: '600'
          }
        ]
      },
      boxShadow: {
        soft: '0 1px 8px rgba(0, 0, 0, 0.04)',
        card: '0 1px 3px 0 rgba(15, 23, 42, 0.05), 0 1px 2px -1px rgba(15, 23, 42, 0.05)',
        popover: '0 4px 6px -1px rgba(15, 23, 42, 0.08), 0 2px 4px -2px rgba(15, 23, 42, 0.06)',
        modal: '0 20px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.08)'
      },
    },
  },
  plugins: [forms],
};
