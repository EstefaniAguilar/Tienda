import React, { useMemo, useState } from "react";
import { ArrowRight, CheckCircle2, Cpu, Monitor } from "lucide-react";
import { PageHeading } from "../compartido/componentes";
import { money } from "../compartido/data";

const groups = [
  { key: "cpu", label: "Procesador", test: /procesador|\bcpu\b|ryzen|intel core|core i[3579]/i },
  { key: "ram", label: "Memoria RAM", test: /memoria|\bram\b|ddr[345]/i },
  { key: "storage", label: "Almacenamiento", test: /ssd|\bhdd\b|disco duro|almacenamiento|nvme/i },
  { key: "gpu", label: "Tarjeta gráfica", test: /tarjeta gráfica|tarjeta de video|\bgpu\b|geforce|\brtx\b|radeon|gráficos integrados/i }
];
const matches = (product, group) => group.test.test(`${product.name || ""} ${product.category || ""} ${product.description || ""} ${(product.attributes || []).map(a => `${a.attribute_name || a.name || ""} ${a.attribute_value || a.value || ""}`).join(" ")}`);

export function PCBuilder({ products = [], addToCart }) {
  const choices = useMemo(() => Object.fromEntries(groups.map(g => [g.key, products.filter(p => p.active && matches(p, g)).sort((a,b) => a.price-b.price)])), [products]);
  const [parts, setParts] = useState({ cpu: "", ram: "", storage: "", gpu: "", use: "Estudio y oficina" });
  const [saved, setSaved] = useState(false);
  const selected = key => choices[key].find(p => String(p.id) === String(parts[key]));
  const total = groups.reduce((sum, group) => sum + (selected(group.key)?.price || 0), 0);
  const setPart = (key, value) => { setParts(current => ({ ...current, [key]: value })); setSaved(false); };
  return <>
    <PageHeading eyebrow="CONFIGURADOR" title="Arma tu PC" subtitle="Selecciona componentes disponibles en la tienda y consulta el precio real de cada uno."/>
    <div className="builder-layout">
      <section className="panel builder-form">
        <div className="panel-heading"><div><h2>Configura tu equipo</h2><p>Los precios se obtienen del catálogo de M&T.</p></div><Cpu/></div>
        <label className="builder-field">Uso principal<select value={parts.use} onChange={e=>setPart("use",e.target.value)}><option>Estudio y oficina</option><option>Diseño e ingeniería</option><option>Gaming</option></select></label>
        {groups.map(group => <label className="builder-field" key={group.key}>{group.label}
          <select value={parts[group.key]} onChange={e=>setPart(group.key,e.target.value)}>
            <option value="">Selecciona {group.label.toLowerCase()}</option>
            {choices[group.key].map(product => <option key={product.id} value={product.id}>{product.name} — {money(product.price)}</option>)}
          </select>
          {selected(group.key) ? <small>Precio: <b>{money(selected(group.key).price)}</b></small> : <small>{choices[group.key].length ? `${choices[group.key].length} opción(es) disponibles` : "No hay productos de esta categoría disponibles en el catálogo."}</small>}
        </label>)}
        <button className="primary-button full" onClick={()=>setSaved(true)} disabled={!groups.every(g=>selected(g.key))}>Generar cotización</button>
      </section>
      <section className="builder-summary">
        <div className="builder-pc-icon"><Monitor size={66}/><Cpu size={27}/></div>
        <span className="eyebrow">TU CONFIGURACIÓN</span><h2>PC M&T personalizada</h2>
        <div className="build-specs"><div><span>Uso</span><b>{parts.use}</b></div>
          {groups.map(group => <div key={group.key}><span>{group.label}</span><b>{selected(group.key)?.name || "Sin seleccionar"}</b></div>)}
        </div>
        <div className="build-specs">{groups.map(group => <div key={group.key}><span>Precio · {group.label}</span><b>{selected(group.key) ? money(selected(group.key).price) : "—"}</b></div>)}</div>
        <div className="estimate-price"><span>Total de componentes</span><strong>{money(total)}</strong></div>
        <p className="fine-print">El total suma los precios actuales de los cuatro productos seleccionados. No incluye componentes adicionales, armado ni envío.</p>
        <button className="primary-button full" onClick={()=>setSaved(true)} disabled={!groups.every(g=>selected(g.key))}>Solicitar cotización <ArrowRight size={15}/></button>
        {saved&&<div className="inline-success"><CheckCircle2 size={16}/> Cotización calculada con los precios actuales del catálogo.</div>}
      </section>
    </div>
  </>;
}
