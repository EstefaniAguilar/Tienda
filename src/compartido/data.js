export const initialProducts = [
  { id: 1, name: "Laptop Lenovo IdeaPad 3", category: "Equipos de cómputo", price: 12499, oldPrice: 13999, stock: 8, minStock: 3, image: "https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=900&q=80", description: "Laptop para actividades académicas, trabajo y productividad.", rating: 4.7, reviews: 28, active: true, featured: true },
  { id: 2, name: "PC de escritorio empresarial", category: "Equipos de cómputo", price: 15999, stock: 4, minStock: 3, image: "https://images.unsplash.com/photo-1587831990711-23ca6441447b?auto=format&fit=crop&w=900&q=80", description: "Equipo de escritorio para oficinas y empresas.", rating: 4.8, reviews: 16, active: true, featured: true },
  { id: 3, name: "Monitor LED 24 pulgadas", category: "Periféricos", price: 2899, stock: 2, minStock: 4, image: "https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=900&q=80", description: "Monitor Full HD ideal para estudio y oficina.", rating: 4.5, reviews: 12, active: true },
  { id: 4, name: "Teclado y mouse inalámbricos", category: "Periféricos", price: 699, stock: 18, minStock: 5, image: "https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=900&q=80", description: "Combo inalámbrico cómodo para uso diario.", rating: 4.4, reviews: 31, active: true },
  { id: 5, name: "Escritorio para oficina", category: "Mobiliario", price: 3299, stock: 6, minStock: 2, image: "https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=900&q=80", description: "Escritorio funcional para espacios de trabajo.", rating: 4.6, reviews: 9, active: true },
  { id: 6, name: "Silla ergonómica ejecutiva", category: "Mobiliario", price: 4199, stock: 3, minStock: 3, image: "https://images.unsplash.com/photo-1505843490701-5be5d?auto=format&fit=crop&w=900&q=80", description: "Silla ajustable para jornadas de trabajo.", rating: 4.3, reviews: 7, active: true },
  { id: 7, name: "Sistema punto de venta", category: "Soluciones empresariales", price: 5499, stock: 12, minStock: 3, image: "https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=900&q=80", description: "Solución de punto de venta para comercios.", rating: 4.9, reviews: 11, active: true, featured: true },
  { id: 8, name: "Sistema de cobro municipal", category: "Software", price: 8900, stock: 0, minStock: 1, image: "https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=900&q=80", description: "Solución de software para cobros de agua, predial y servicios municipales. Producto demostrativo.", rating: 4.8, reviews: 5, active: true },
  { id: 9, name: "Licencia de software administrativo", category: "Software", price: 2199, stock: 15, minStock: 4, image: "https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=900&q=80", description: "Herramientas digitales para administración de negocios.", rating: 4.6, reviews: 19, active: true },
  { id: 10, name: "Tarjeta gráfica RTX de demostración", category: "Componentes PC", price: 9999, stock: 5, minStock: 2, image: "https://images.unsplash.com/photo-1591488320449-011701bb6704?auto=format&fit=crop&w=900&q=80", description: "Componente gráfico para estaciones de trabajo y gaming.", rating: 4.9, reviews: 22, active: true },
  { id: 11, name: "Memoria RAM 16 GB DDR4", category: "Componentes PC", price: 899, stock: 20, minStock: 5, image: "https://images.unsplash.com/photo-1562976540-1502c2145186?auto=format&fit=crop&w=900&q=80", description: "Memoria para ampliar el rendimiento de tu computadora.", rating: 4.5, reviews: 14, active: true }
];
export const initialOrders = [
  { id: "MT-1001", customer: "María Fernanda López", email: "maria@demo.com", date: "2026-11-23", total: 13198, payment: "Contra entrega", status: "Nuevo", items: [{ productId: 1, name: "Laptop Lenovo IdeaPad 3", qty: 1, price: 12499 }, { productId: 4, name: "Teclado y mouse inalámbricos", qty: 1, price: 699 }] },
  { id: "MT-1002", customer: "Carlos Hernández", email: "carlos@demo.com", date: "2026-11-24", total: 2899, payment: "Pago de prueba", status: "Preparado", items: [{ productId: 3, name: "Monitor LED 24 pulgadas", qty: 1, price: 2899 }] }
];
export const initialReviews = [
  { id: 1, productId: 1, author: "Andrea M.", rating: 5, comment: "Muy buena para mis clases y proyectos.", date: "20/11/2026" },
  { id: 2, productId: 1, author: "Luis R.", rating: 4, comment: "Buen rendimiento y entrega rápida.", date: "18/11/2026" },
  { id: 3, productId: 7, author: "Comercial del Centro", rating: 5, comment: "El sistema es práctico para nuestro negocio.", date: "15/11/2026" }
];
export const initialCoupons = [{ code: "MT10", discount: 10, active: true }, { code: "ESTUDIANTE", discount: 5, active: true }, { code: "BIENVENIDA", discount: 15, active: true }];
export const money = value => new Intl.NumberFormat("es-MX", { style: "currency", currency: "MXN" }).format(Number(value) || 0);

// Las cuentas del personal se validan exclusivamente en el backend PHP.
