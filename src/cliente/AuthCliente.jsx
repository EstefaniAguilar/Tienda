import React, { useState } from "react";
import { X } from "lucide-react";
import { api } from "../compartido/api";

const norm = v => v.trim().toLowerCase();

// Registro / ingreso del cliente. Solo aparece cuando quiere comprar o consultar sus pedidos.
export function AuthCliente({ reason, onClose, onSuccess }) {
  const [mode, setMode] = useState("registro");
  const [form, setForm] = useState({ name: "", email: "", password: "", confirm: "" });
  const [error, setError] = useState("");
  const set = (k, v) => { setForm(f => ({ ...f, [k]: v })); setError(""); };

  const submit = async e => {
    e.preventDefault();
    const email = norm(form.email);
    if (mode === "registro") {
      if (form.name.trim().length < 2) return setError("Escribe tu nombre completo.");
      if (form.password.length < 12) return setError("La contraseña debe tener al menos 12 caracteres.");
      if (form.password !== form.confirm) return setError("Las contraseñas no coinciden.");
    }
    try {
      const result = await api(mode === "registro" ? "register" : "login", {
        name: form.name.trim(), email, password: form.password
      });
      if (result.user.profile !== "Cliente") { await api("logout", {}); return setError("Esta cuenta pertenece al personal. Utiliza el acceso de administrador, vendedor o repartidor."); }
      onSuccess(result.user);
    } catch (err) { setError(err.message); }
  };

  return <div className="modal-backdrop"><div className="modal login-modal"><div className="modal-heading"><div><span className="eyebrow">CUENTA DE CLIENTE</span><h2>{mode === "registro" ? "Crea tu cuenta" : "Inicia sesión"}</h2></div><button className="icon-button" onClick={onClose}><X/></button></div>
    <p className="auth-intro">{reason}</p>
    <div className="login-tabs"><button className={mode === "registro" ? "active" : ""} onClick={() => { setMode("registro"); setError(""); }}>Crear cuenta</button><button className={mode === "login" ? "active" : ""} onClick={() => { setMode("login"); setError(""); }}>Ya tengo cuenta</button></div>
    <form className="checkout-form" onSubmit={submit}>
      {mode === "registro" && <label>Nombre completo<input required value={form.name} onChange={e => set("name", e.target.value)} placeholder="Tu nombre"/></label>}
      <label>Correo electrónico<input type="email" required value={form.email} onChange={e => set("email", e.target.value)} placeholder="correo@ejemplo.com"/></label>
      <label>Contraseña<input type="password" required value={form.password} onChange={e => set("password", e.target.value)} placeholder={mode === "registro" ? "Mínimo 12 caracteres" : "Tu contraseña"}/></label>
      {mode === "registro" && <label>Confirmar contraseña<input type="password" required value={form.confirm} onChange={e => set("confirm", e.target.value)} placeholder="Repite tu contraseña"/></label>}
      {error && <div className="form-error">{error}</div>}
      <button className="primary-button full" type="submit">{mode === "registro" ? "Registrarme y continuar" : "Ingresar y continuar"}</button>
      <p className="secure-note">Cuenta protegida y almacenada en la base de datos.</p>
    </form></div></div>;
}
