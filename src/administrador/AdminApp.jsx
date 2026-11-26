import React, { useState } from "react";
import { LayoutDashboard, ClipboardList, Package, Boxes } from "lucide-react";
import Estructura, { ChipUsuario } from "../compartido/Estructura";
import { NotificationsModal, ReceiptModal } from "../compartido/componentes";
import { Dashboard } from "../compartido/Resumen";
import { Orders } from "../compartido/Pedidos";
import { Inventory } from "../compartido/Inventario";
import { Products, ProductModal } from "./Productos";
import { api } from "../compartido/api";

// Módulo ADMINISTRADOR: todo lo del vendedor + alta, edición y baja de productos y edición de inventario.
export default function AdminApp({ store }) {
  const { products, setProducts, orders, dark, setDark, notifications, setNotifications, notice, toast, user, setUser, changeStatus, refresh } = store;
  const [page, setPage] = useState("dashboard");
  const [modal, setModal] = useState(null);
  const activos = products.filter(p => p.active);
  const lowStock = activos.filter(p => p.stock > 0 && p.stock <= p.minStock);
  const outStock = activos.filter(p => p.stock === 0);
  const nav = [{ title: "ADMINISTRACIÓN", items: [["dashboard", "Dashboard", LayoutDashboard], ["orders", "Pedidos", ClipboardList], ["products", "Productos", Package], ["inventory", "Inventario", Boxes]] }];
  const logout = async () => { try { await api("logout", {}); } catch {} setUser(null); localStorage.removeItem("mt_sesion"); toast("Sesión cerrada."); };
  const saveProduct = async product => { try { await api("save-product",{product:{...product,active:true}}); await refresh(); setModal(null); toast("Producto guardado en MySQL."); } catch(e) { toast(e.message); } };
  const deleteProduct = async id => { if (!window.confirm("¿Deseas dar de baja este producto?")) return; const p=products.find(x=>x.id===id); if(!p)return; try { await api("save-product",{product:{...p,active:false}}); await refresh(); toast("Producto dado de baja."); } catch(e) { toast(e.message); } };
  const overlays = <>
    {modal?.type === "product" && <ProductModal product={modal.product} onClose={() => setModal(null)} onSave={saveProduct}/>}
    {modal?.type === "receipt" && <ReceiptModal order={modal.order} onClose={() => setModal(null)}/>}
    {modal?.type === "notifications" && <NotificationsModal items={notifications} onClose={() => setModal(null)} clear={() => setNotifications([])}/>}
  </>;
  return <Estructura dark={dark} setDark={setDark} notifCount={notifications.length} onNotifs={() => setModal({ type: "notifications" })} right={<ChipUsuario user={user} onLogout={logout}/>} nav={nav} badges={{ orders: orders.length }} page={page} onNavigate={setPage} home="dashboard" notice={notice} overlays={overlays}>
    {page === "dashboard" && <Dashboard products={products} orders={orders} lowStock={lowStock} outStock={outStock} setPage={setPage}/>}
    {page === "orders" && <Orders orders={orders} role="Administrador" changeStatus={changeStatus} onReceipt={o => setModal({ type: "receipt", order: o })}/>}
    {page === "products" && <Products products={activos} onAdd={() => setModal({ type: "product" })} onEdit={p => setModal({ type: "product", product: p })} onDelete={deleteProduct}/>}
    {page === "inventory" && <Inventory products={activos} lowStock={lowStock} outStock={outStock} onEdit={p => setModal({ type: "product", product: p })}/>}
  </Estructura>;
}
