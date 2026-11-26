import React, { useEffect, useMemo, useState } from "react";
import { Store, Cpu, GitCompareArrows, Building2, Wrench, ClipboardList, ShoppingCart, LogIn, X, Minus, Plus, Trash2, BadgePercent, CheckCircle2, ArrowRight, CircleHelp, Laptop, RefreshCw } from "lucide-react";
import Estructura, { ChipUsuario } from "../compartido/Estructura";
import { PageHeading, NotificationsModal, ReceiptModal } from "../compartido/componentes";
import { IngresoPersonal } from "../compartido/IngresoPersonal";
import { Orders } from "../compartido/Pedidos";
import { money } from "../compartido/data";
import { read, save } from "../compartido/utils";
import { Catalog } from "./Catalogo";
import { PCBuilder } from "./ArmaPC";
import { Compare } from "./Comparador";
import { Services } from "./Servicios";
import { Repair } from "./Reparacion";
import { Help } from "./Ayuda";
import { CheckoutModal, DetailsModal, ReviewModal, ReserveModal } from "./Modales";
import { ChatWidget } from "./Chat";
import { AuthCliente } from "./AuthCliente";
import { api } from "../compartido/api";
import { Posventa } from "./Posventa";

// Módulo CLIENTE: navega y explora sin cuenta. Solo debe registrarse para comprar.
export default function ClienteApp({ store }) {
  const { products, setProducts, orders, setOrders, reviews, setReviews, coupons, dark, setDark, notifications, setNotifications, notice, toast, notify, user, setUser } = store;
  const [cart, setCart] = useState(() => read("mt_cart", []));
  const [page, setPage] = useState("catalog");
  const [search, setSearch] = useState("");
  const [category, setCategory] = useState("Todas");
  const [minPrice, setMinPrice] = useState("");
  const [maxPrice, setMaxPrice] = useState("");
  const [minRating, setMinRating] = useState("0");
  const [sort, setSort] = useState("featured");
  const [cartOpen, setCartOpen] = useState(false);
  const [modal, setModal] = useState(null);
  const [couponCode, setCouponCode] = useState("");
  const [appliedCoupon, setAppliedCoupon] = useState(null);
  const [chatOpen, setChatOpen] = useState(false);
  const [chatMessages, setChatMessages] = useState([{ from: "bot", text: "¡Hola! Soy el asistente virtual de M&T. Puedo ayudarte con compras, pedidos, cotizaciones y soporte técnico." }]);
  const [chatInput, setChatInput] = useState("");
  const [compareIds, setCompareIds] = useState([1, 2]);

  useEffect(() => save("mt_cart", cart), [cart]);

  const categories = ["Todas", ...new Set(products.map(p => p.category))];
  const visibleProducts = useMemo(() => {
    let list = products.filter(p => p.active && p.name.toLowerCase().includes(search.toLowerCase()) && (category === "Todas" || p.category === category) && (!minPrice || p.price >= Number(minPrice)) && (!maxPrice || p.price <= Number(maxPrice)) && p.rating >= Number(minRating));
    if (sort === "price-low") list.sort((a, b) => a.price - b.price);
    if (sort === "price-high") list.sort((a, b) => b.price - a.price);
    if (sort === "rating") list.sort((a, b) => b.rating - a.rating);
    if (sort === "featured") list.sort((a, b) => Number(!!b.featured) - Number(!!a.featured));
    return list;
  }, [products, search, category, minPrice, maxPrice, minRating, sort]);
  const cartCount = cart.reduce((s, i) => s + i.qty, 0);
  const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
  const discount = appliedCoupon ? subtotal * appliedCoupon.discount / 100 : 0;
  const cartTotal = Math.max(0, subtotal - discount);
  const myOrders = user ? orders.filter(o => o.email?.toLowerCase() === user.email.toLowerCase()) : [];

  const go = target => { setPage(target); setCartOpen(false); };
  const addToCart = product => {
    if (product.stock < 1) return toast("Este producto está agotado.");
    setCart(current => {
      const found = current.find(item => item.id === product.id);
      if (found) { if (found.qty >= product.stock) { toast("No hay más existencias disponibles."); return current; } return current.map(item => item.id === product.id ? { ...item, qty: item.qty + 1 } : item); }
      return [...current, { id: product.id, name: product.name, price: product.price, image: product.image, qty: 1 }];
    });
    toast("Producto agregado al carrito.");
  };
  const updateQty = (id, delta) => setCart(current => current.map(item => {
    if (item.id !== id) return item;
    const p = products.find(product => product.id === id);
    return { ...item, qty: Math.max(1, Math.min(p?.stock ?? 1, item.qty + delta)) };
  }));
  const removeCart = id => setCart(current => current.filter(item => item.id !== id));
  const applyCoupon = () => {
    const found = coupons.find(c => c.code.toUpperCase() === couponCode.trim().toUpperCase() && c.active);
    if (!found) { setAppliedCoupon(null); return toast("Cupón no válido."); }
    setAppliedCoupon(found); toast(`Cupón aplicado: ${found.discount}% de descuento.`);
  };

  // Aquí está la puerta: sin cuenta no se puede finalizar una compra.
  const startCheckout = () => setModal(user ? { type: "checkout" } : { type: "auth", next: "checkout" });
  const onAuth = (account, next) => {
    setUser({ name: account.name, email: account.email, profile: "Cliente" });
    toast(`Bienvenido, ${account.name.split(" ")[0]}.`);
    if (next === "checkout") setModal({ type: "checkout" });
    else { setModal(null); if (next === "orders") go("orders"); }
  };
  const logout = async () => { try { await api("logout", {}); } catch {} setUser(null); localStorage.removeItem("mt_sesion"); go("catalog"); toast("Sesión cerrada."); };
  const onStaffLogin = account => { setUser({ name: account.name, email: account.email, profile: account.profile }); toast(`Sesión iniciada como ${account.profile}.`); };

  const checkout = async (method, customer) => {
    if (!cart.length) return;
    try {
      const result = await api("create-order", {
        customer: customer.name, address: customer.address, payment: method,
        coupon: appliedCoupon?.code || "",
        items: cart.map(i => ({ productId: i.id, qty: i.qty }))
      });
      const order = result.order;
      await storeRefresh();
      setCart([]); setAppliedCoupon(null); setCouponCode("");
      setModal({ type: "receipt", order }); go("orders");
      notify(`Nuevo pedido ${order.id} recibido por ${money(order.total)}.`);
      toast(`Compra registrada. Folio ${order.id}`);
    } catch (err) { toast(err.message); }
  };
  const storeRefresh = async () => { await api("bootstrap").then(data => {
    setProducts(data.products || []); setOrders(data.orders || []); setReviews(data.reviews || []);
  }); };

  const addReview = async review => { try { await api("review", review); await storeRefresh(); setModal(null); toast("Gracias por compartir tu opinión."); } catch (err) { toast(err.message); } };

  const nav = [{ title: "TIENDA", items: [["catalog", "Catálogo", Store], ["builder", "Arma tu PC", Cpu], ["compare", "Comparar PCs", GitCompareArrows], ["services", "Pago de servicios", Building2], ["repair", "Soporte y reparación", Wrench], ["orders", "Mis pedidos", ClipboardList], ["aftersales", "Devoluciones y garantías", RefreshCw]] }];

  const right = <>
    {user ? <ChipUsuario user={user} onLogout={logout}/> : <button className="login-top" onClick={() => setModal({ type: "staff" })}><LogIn size={15}/> Ingresar</button>}
    <button className="cart-button" onClick={() => setCartOpen(true)}><ShoppingCart size={19}/><span>Carrito</span><b>{cartCount}</b></button>
  </>;
  const bottom = <>
    <button className="nav-item" onClick={() => go("help")}><CircleHelp size={18}/> Centro de ayuda</button>
    <div className="help-card"><div className="help-icon"><Laptop size={18}/></div><b>¿Necesitas ayuda?</b><p>Estamos para ayudarte con tus soluciones tecnológicas.</p><button className="help-link" onClick={() => setChatOpen(true)}>Hablar con asistente →</button></div>
  </>;

  const overlays = <>
    {cartOpen && <div className="overlay" onMouseDown={e => e.target === e.currentTarget && setCartOpen(false)}><aside className="cart-drawer"><div className="drawer-head"><div><span className="eyebrow">TU COMPRA</span><h2>Carrito <span className="muted">({cartCount})</span></h2></div><button className="icon-button" onClick={() => setCartOpen(false)}><X/></button></div>
      {!cart.length ? <div className="empty-state"><ShoppingCart size={42}/><h3>Tu carrito está vacío</h3><p>Agrega productos del catálogo para comenzar.</p><button className="primary-button" onClick={() => setCartOpen(false)}>Explorar catálogo</button></div> : <><div className="cart-items">{cart.map(item => <div className="cart-line" key={item.id}><img src={item.image} alt=""/><div className="cart-line-info"><b>{item.name}</b><strong>{money(item.price)}</strong><div className="quantity"><button onClick={() => updateQty(item.id, -1)}><Minus size={13}/></button><span>{item.qty}</span><button onClick={() => updateQty(item.id, 1)}><Plus size={13}/></button></div></div><button className="icon-button danger-text" onClick={() => removeCart(item.id)}><Trash2 size={17}/></button></div>)}</div>
      <div className="coupon-row"><input value={couponCode} onChange={e => setCouponCode(e.target.value)} placeholder="Código de cupón (MT10)"/><button onClick={applyCoupon}>Aplicar</button></div>{appliedCoupon && <div className="coupon-applied"><BadgePercent size={15}/> {appliedCoupon.code}: {appliedCoupon.discount}% de descuento <button onClick={() => setAppliedCoupon(null)}>Quitar</button></div>}
      <div className="checkout-box"><div className="total-row"><span>Subtotal</span><b>{money(subtotal)}</b></div>{discount > 0 && <div className="total-row"><span>Descuento</span><b className="success-text">-{money(discount)}</b></div>}<div className="total-row"><span>Envío</span><b className="success-text">Por calcular</b></div><div className="total-row grand-total"><span>Total</span><strong>{money(cartTotal)}</strong></div><button className="primary-button full" onClick={startCheckout}>Continuar compra <ArrowRight size={16}/></button><p className="secure-note"><CheckCircle2 size={14}/> Compra de demostración · Sin cargos reales</p></div></>}
    </aside></div>}
    {modal?.type === "auth" && <AuthCliente reason={modal.next === "checkout" ? "Para finalizar tu compra necesitas una cuenta. Crea una en un minuto o ingresa con la que ya tienes; tu carrito se conserva." : "Ingresa con tu cuenta de cliente para consultar tus pedidos y comprobantes."} onClose={() => setModal(null)} onSuccess={account => onAuth(account, modal.next)}/>}
    {modal?.type === "staff" && <IngresoPersonal onClose={() => setModal(null)} onLogin={onStaffLogin}/>}
    {modal?.type === "checkout" && <CheckoutModal total={cartTotal} user={user} onClose={() => setModal(null)} onPay={checkout}/>}
    {modal?.type === "details" && <DetailsModal product={modal.product} reviews={reviews.filter(r => r.productId === modal.product.id)} onClose={() => setModal(null)} onReview={() => setModal({ type: "review", product: modal.product })} addToCart={addToCart}/>}
    {modal?.type === "review" && <ReviewModal product={modal.product} onClose={() => setModal(null)} onSave={addReview}/>}
    {modal?.type === "reserve" && <ReserveModal product={modal.product} onClose={() => setModal(null)} toast={toast} notify={notify}/>}
    {modal?.type === "receipt" && <ReceiptModal order={modal.order} onClose={() => setModal(null)}/>}
    {modal?.type === "notifications" && <NotificationsModal items={notifications} onClose={() => setModal(null)} clear={() => setNotifications([])}/>}
    {chatOpen && <ChatWidget messages={chatMessages} setMessages={setChatMessages} value={chatInput} setValue={setChatInput} onClose={() => setChatOpen(false)}/>}
  </>;

  return <Estructura dark={dark} setDark={setDark} notifCount={notifications.length} onNotifs={() => setModal({ type: "notifications" })} search={search} onSearch={v => { setSearch(v); go("catalog"); }} right={right} nav={nav} badges={{ orders: myOrders.length }} page={page} onNavigate={go} home="catalog" bottom={bottom} notice={notice} overlays={overlays}>
    {page === "catalog" && <Catalog products={visibleProducts} categories={categories} category={category} setCategory={setCategory} minPrice={minPrice} setMinPrice={setMinPrice} maxPrice={maxPrice} setMaxPrice={setMaxPrice} minRating={minRating} setMinRating={setMinRating} sort={sort} setSort={setSort} addToCart={addToCart} onDetails={p => setModal({ type: "details", product: p })} onReview={p => setModal({ type: "review", product: p })} onReserve={p => setModal({ type: "reserve", product: p })}/>}
    {page === "aftersales" && (user ? <Posventa orders={myOrders} toast={toast}/> : <div className="empty-state"><h3>Ingresa para consultar tus compras</h3><button className="primary-button" onClick={() => setModal({ type: "auth", next: "orders" })}>Ingresar</button></div>)}
    {page === "orders" && (user
      ? <Orders orders={myOrders} role="Cliente" changeStatus={() => {}} onReceipt={o => setModal({ type: "receipt", order: o })}/>
      : <><PageHeading eyebrow="SEGUIMIENTO" title="Historial de pedidos" subtitle="Consulta tus compras, folios y comprobantes."/><div className="empty-state"><ClipboardList size={42}/><h3>Ingresa para ver tus pedidos</h3><p>Tus compras se guardan en tu cuenta de cliente.</p><button className="primary-button" onClick={() => setModal({ type: "auth", next: "orders" })}>Crear cuenta o ingresar</button></div></>)}
    {page === "builder" && <PCBuilder products={products} addToCart={addToCart}/>}
    {page === "compare" && <Compare products={products} compareIds={compareIds} setCompareIds={setCompareIds}/>}
    {page === "services" && <Services toast={toast} notify={notify}/>}
    {page === "repair" && <Repair toast={toast} notify={notify}/>}
    {page === "help" && <Help onChat={() => setChatOpen(true)} go={go}/>}
  </Estructura>;
}
