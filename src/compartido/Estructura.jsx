import React, { useState } from "react";
import { Search, Moon, Sun, Bell, Menu, LogOut, CheckCircle2 } from "lucide-react";

export function ChipUsuario({ user, onLogout }) {
  return <button className="user-chip" onClick={onLogout} title="Cerrar sesión"><span>{user.name.slice(0, 1).toUpperCase()}</span><b>{user.name.split(" ")[0]}</b><LogOut size={14}/></button>;
}

// Marco común: barra superior + menú lateral. Cada rol le pasa su propio menú y acciones.
export default function Estructura({ dark, setDark, notifCount, onNotifs, search, onSearch, right, nav, badges = {}, page, onNavigate, home, bottom, notice, overlays, children }) {
  const [mobileNav, setMobileNav] = useState(false);
  const go = id => { setMobileNav(false); onNavigate(id); };
  return <div className={`app-shell ${dark ? "dark" : ""}`}>
    <header className="topbar">
      <button className="icon-button mobile-menu" onClick={() => setMobileNav(!mobileNav)} aria-label="Abrir menú"><Menu/></button>
      <button className="brand" onClick={() => go(home)}><span className="brand-mark">M<span>&</span>T</span><span className="brand-copy">SOLUCIONES<br/>TECNOLÓGICAS</span></button>
      {onSearch && <div className="top-search"><Search size={18}/><input value={search} onChange={e => onSearch(e.target.value)} placeholder="Buscar equipos, software, mobiliario..."/></div>}
      <div className="top-actions">
        <button className="icon-button header-icon" title="Cambiar tema" onClick={() => setDark(v => !v)}>{dark ? <Sun/> : <Moon/>}</button>
        <button className="icon-button header-icon notification-trigger" title="Notificaciones" onClick={onNotifs}><Bell/><i>{notifCount}</i></button>
        {right}
      </div>
    </header>
    <div className="layout">
      <aside className={`sidebar ${mobileNav ? "sidebar-open" : ""}`} onClick={e => e.target.closest("button") && setMobileNav(false)}>
        {nav.map(group => <div key={group.title}><div className="side-label">{group.title}</div>{group.items.map(([id, label, Icon]) => <button key={id} className={`nav-item ${page === id ? "active" : ""}`} onClick={() => go(id)}><Icon size={18}/>{label}{badges[id] > 0 && <span className="nav-count">{badges[id]}</span>}</button>)}</div>)}
        <div className="sidebar-bottom">{bottom}<small>© 2026 M&T · Demo de tienda virtual</small></div>
      </aside>
      <main className="main-content">{children}</main>
    </div>
    {overlays}
    {notice && <div className="toast"><CheckCircle2 size={18}/>{notice}</div>}
  </div>;
}
