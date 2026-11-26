import { useEffect, useState } from "react";
import { initialProducts, initialOrders, initialReviews, initialCoupons } from "./data";
import { read, save } from "./utils";
import { api } from "./api";

export function useTienda() {
  const [products,setProducts]=useState(initialProducts);
  const [orders,setOrders]=useState(initialOrders);
  const [reviews,setReviews]=useState(initialReviews);
  const [coupons,setCoupons]=useState(initialCoupons);
  const [dark,setDark]=useState(()=>read("mt_dark",false));
  const [notifications,setNotifications]=useState(()=>read("mt_notifications",["Bienvenido a M&T."]));
  const [user,setUser]=useState(()=>read("mt_sesion",null));
  const [notice,setNotice]=useState("");

  const refresh=async()=>{
    const data=await api("bootstrap");
    setProducts(data.products||[]);setOrders(data.orders||[]);
    setReviews(data.reviews||[]);setCoupons(data.coupons||[]);
    setUser(data.user || null); if(data.user) save("mt_sesion",data.user); else localStorage.removeItem("mt_sesion");
  };
  useEffect(()=>{
    const load=()=>refresh().catch(e=>setNotice(`No se pudo conectar con MySQL: ${e.message}`));
    load();
    const interval=window.setInterval(load,15000);
    const onFocus=()=>load();
    window.addEventListener("focus",onFocus);
    return ()=>{window.clearInterval(interval);window.removeEventListener("focus",onFocus);};
  },[]);
  useEffect(()=>save("mt_dark",dark),[dark]);
  useEffect(()=>save("mt_notifications",notifications),[notifications]);
  useEffect(()=>{if(user)save("mt_sesion",user);else localStorage.removeItem("mt_sesion");},[user]);

  const toast=message=>{setNotice(message);window.setTimeout(()=>setNotice(""),3500);};
  const notify=message=>setNotifications(n=>[message,...n].slice(0,30));
  const changeStatus=async(id,status)=>{
    try{await api("order-status",{folio:id,status});setOrders(current=>current.map(o=>o.id===id?{...o,status}:o));notify(`El pedido ${id} cambió a ${status.toLowerCase()}.`);toast("Estado del pedido actualizado.");}
    catch(e){toast(e.message);}
  };
  return {products,setProducts,orders,setOrders,reviews,setReviews,coupons,dark,setDark,notifications,setNotifications,user,setUser,notice,toast,notify,changeStatus,refresh};
}
