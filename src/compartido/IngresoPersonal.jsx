import React, { useState } from "react";
import { X } from "lucide-react";
import { api } from "./api";

// Botón "Ingresar": solo acepta cuentas de Administrador o Vendedor. El rol lo define la cuenta.
export function IngresoPersonal({ onClose, onLogin }) {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const submit = async e => {
    e.preventDefault();
    try { const result = await api("login", { email: email.trim().toLowerCase(), password }); if (!["Administrador","Vendedor","Repartidor"].includes(result.user.profile)) { await api("logout", {}); return setError("Esta entrada es solo para administrador, vendedor y repartidor."); } onLogin(result.user); } catch (err) { setError(err.message); }
  };
  return <div className="modal-backdrop"><div className="modal login-modal"><div className="modal-heading"><div><span className="eyebrow">ACCESO DEL PERSONAL</span><h2>Iniciar sesión</h2></div><button className="icon-button" onClick={onClose}><X/></button></div>
    <p className="auth-intro">Solo para cuentas de <b>administrador</b> y <b>vendedor</b>. Si eres cliente no necesitas ingresar: explora la tienda libremente y te pediremos registro hasta que quieras comprar.</p>
    <form className="checkout-form" style={{ marginTop: 18 }} onSubmit={submit}>
      <label>Correo electrónico<input type="email" required autoFocus value={email} onChange={e => { setEmail(e.target.value); setError(""); }} placeholder="usuario@mt.com"/></label>
      <label>Contraseña<input type="password" required value={password} onChange={e => { setPassword(e.target.value); setError(""); }} placeholder="Tu contraseña"/></label>
      {error && <div className="form-error">{error}</div>}
      <button className="primary-button full" type="submit">Ingresar</button>
      <div className="demo-creds">Las cuentas de personal deben ser creadas previamente en la base de datos por el administrador del sistema.</div>
    </form></div></div>;
}
