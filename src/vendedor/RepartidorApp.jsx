import React from "react";
import { Truck } from "lucide-react";
import Estructura,{ChipUsuario} from "../compartido/Estructura";
import { EntregasVendedor } from "./OperacionesVendedor";
import { api } from "../compartido/api";
export default function RepartidorApp({store}){
 const {orders,dark,setDark,notifications,setNotifications,notice,toast,user,setUser,changeStatus,refresh}=store;
 const logout=async()=>{try{await api("logout",{});}catch{}setUser(null);localStorage.removeItem("mt_sesion");};
 return <Estructura dark={dark} setDark={setDark} notifCount={notifications.length} onNotifs={()=>toast("Consulta las entregas asignadas en esta pantalla.")} right={<ChipUsuario user={user} onLogout={logout}/>} nav={[{title:"REPARTO",items:[["deliveries","Mis entregas",Truck]]}]} page="deliveries" onNavigate={()=>{}} home="deliveries" notice={notice} overlays={null}><EntregasVendedor orders={orders} changeStatus={changeStatus} toast={toast} refresh={refresh}/></Estructura>;
}
