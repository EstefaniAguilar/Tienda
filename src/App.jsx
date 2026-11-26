import React from "react";
import { useTienda } from "./compartido/useTienda";
import ClienteApp from "./cliente/ClienteApp";
import VendedorApp from "./vendedor/VendedorApp";
import AdminApp from "./administrador/AdminApp";
import RepartidorApp from "./vendedor/RepartidorApp";

// Elige el módulo según la sesión: administrador, vendedor o cliente/visitante.
export default function App() {
  const store = useTienda();
  const perfil = store.user?.profile;
  if (perfil === "Administrador") return <AdminApp store={store}/>;
  if (perfil === "Vendedor") return <VendedorApp store={store}/>;
  if (perfil === "Repartidor") return <RepartidorApp store={store}/>;
  return <ClienteApp store={store}/>;
}
