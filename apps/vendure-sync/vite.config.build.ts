import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import cssInjectedByJsPlugin from 'vite-plugin-css-injected-by-js'
import { resolve } from 'path'
import replace from '@rollup/plugin-replace'
import terser from '@rollup/plugin-terser'

export default defineConfig({
  root: __dirname,
  plugins: [
    react({
      jsxRuntime: 'automatic',
    }),
    cssInjectedByJsPlugin({
      jsAssetsFilterFunction: function customJsAssetsFilterFunction(outputChunk) {
        if (outputChunk.isEntry) {
          return true
        }
        return false
      },
      injectCodeFunction: function injectCodeCustomRunTimeFunction(cssCode) {
        function injectCss(cssCode: string | null) {
          const styleEl = document.createElement('style')
          styleEl.textContent = cssCode
          styleEl.id = 'vendure-sync-injected-css'

          if (document.head) {
            const existing = document.head.querySelector('#vendure-sync-injected-css')
            if (existing) {
              existing.remove()
            }
            document.head.appendChild(styleEl)
          }
        }

        try {
          if (typeof document !== 'undefined') {
            if (document.readyState !== 'loading') {
              injectCss(cssCode)
            } else {
              document.addEventListener('DOMContentLoaded', () => {
                injectCss(cssCode)
              })
            }
          }
        } catch (e) {
          console.error('Error injecting CSS for Vendure Sync', e)
        }
      },
    }),
  ],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    manifest: true,
    minify: 'terser' as const,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'src/main.tsx'),
      },
      plugins: [
        replace({
          'process.env.NODE_ENV': JSON.stringify('production'),
          preventAssignment: true,
        }),
        terser(),
      ],
      output: {
        format: 'es' as const,
        entryFileNames: '[name]-[hash].js',
        chunkFileNames: '[name]-[hash].js',
      },
    },
  },
  define: {
    'process.env': {},
    __DEV__: false,
    global: 'globalThis',
  },
})
