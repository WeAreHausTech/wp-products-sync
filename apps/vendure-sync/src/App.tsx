import './App.css'
import VendureSync from './VendureSync'
import { ToastProvider } from './contexts/ToastContext'

function App() {
  return (
    <ToastProvider>
      <div className="vendure-sync-app">
        <VendureSync />
      </div>
    </ToastProvider>
  )
}

export default App
