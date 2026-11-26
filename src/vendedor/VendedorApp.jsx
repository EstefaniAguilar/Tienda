import React, { useState } from "react";
import { LayoutDashboard, ClipboardList, Boxes, Package, Truck, RefreshCw } from "lucide-react";
import Estructura, { ChipUsuario } from "../compartido/Estructura";
import { NotificationsModal, ReceiptModal } from "../compartido/componentes";
import { Dashboard } from "../compartido/Resumen";
import { Orders } from "../compartido/Pedidos";
import { Inventory } from "../compartido/Inventario";
import { api } from "../compartido/api";
import { ProductosVendedor } from "./ProductosVendedor";
import { EntregasVendedor, DevolucionesVendedor } from "./OperacionesVendedor";

// Módulo VENDEDOR: ve el resumen, atiende pedidos (cambia su estado) y consulta el inventario.
export default function VendedorApp({ store }) {
  const { products, orders, dark, setDark, notifications, setNotifications, notice, toast, user, setUser, changeStatus } = store;
  const [page, setPage] = useState("dashboard");
  const [modal, setModal] = useState(null);
  const activos = products.filter(p => p.active);
  const lowStock = activos.filter(p => p.stock > 0 && p.stock <= p.minStock);
  const outStock = activos.filter(p => p.stock === 0);
  const nav = [{ title: "VENTAS", items: [["dashboard", "Resumen", LayoutDashboard], ["products", "Mis productos", Package], ["orders", "Pedidos", ClipboardList], ["deliveries", "Entregas", Truck], ["returns", "Devoluciones y garantías", RefreshCw], ["inventory", "Inventario", Boxes]] }];
  const logout = async () => { try { await api("logout", {}); } catch {} setUser(null); localStorage.removeItem("mt_sesion"); toast("Sesión cerrada."); };
  const overlays = <>
    {modal?.type === "receipt" && <ReceiptModal order={modal.order} onClose={() => setModal(null)}/>}
    {modal?.type === "notifications" && <NotificationsModal items={notifications} onClose={() => setModal(null)} clear={() => setNotifications([])}/>}
  </>;
  return <Estructura dark={dark} setDark={setDark} notifCount={notifications.length} onNotifs={() => setModal({ type: "notifications" })} right={<ChipUsuario user={user} onLogout={logout}/>} nav={nav} badges={{ orders: orders.length }} page={page} onNavigate={setPage} home="dashboard" notice={notice} overlays={overlays}>
    {page === "dashboard" && <Dashboard products={products} orders={orders} lowStock={lowStock} outStock={outStock} setPage={setPage}/>}
    {page === "orders" && <Orders orders={orders} role="Vendedor" changeStatus={changeStatus} onReceipt={o => setModal({ type: "receipt", order: o })}/>}
    {page === "inventory" && <Inventory products={activos} lowStock={lowStock} outStock={outStock}/>}
    {page === "products" && <ProductosVendedor products={activos} refresh={store.refresh} toast={toast}/>}
    {page === "deliveries" && <EntregasVendedor orders={orders} changeStatus={changeStatus} toast={toast} refresh={store.refresh}/>}
    {page === "returns" && <DevolucionesVendedor toast={toast}/>}
  </Estructura>;
}
