<?php
require __DIR__.'/config.php';
try {
  $action = $_GET['action'] ?? '';
  $pdo = db();
  requireCsrf();

  if ($action === 'bootstrap' && $_SERVER['REQUEST_METHOD']==='GET') {
    $profile=$_SESSION['user']['profile']??'';$uid=(int)($_SESSION['user']['id']??0);$pq=($profile==='Repartidor')?$pdo->prepare('SELECT * FROM products WHERE 1=0'):($profile==='Vendedor'?$pdo->prepare('SELECT * FROM products WHERE owner_id=? ORDER BY id DESC'):$pdo->prepare('SELECT * FROM products ORDER BY id DESC'));if($profile==='Vendedor')$pq->execute([$uid]);else $pq->execute();$products=array_map('productRow',$pq->fetchAll());
    if (($_SESSION['user']['profile'] ?? '') === 'Cliente') {
      $os=$pdo->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY order_date DESC');$os->execute([$_SESSION['user']['id']]);$orderRows=$os->fetchAll();
    } elseif (($_SESSION['user']['profile'] ?? '') === 'Vendedor') {
      $os=$pdo->prepare('SELECT * FROM orders WHERE seller_id=? ORDER BY order_date DESC');$os->execute([$_SESSION['user']['id']]);$orderRows=$os->fetchAll();
    } elseif (($_SESSION['user']['profile'] ?? '') === 'Repartidor') {
      $os=$pdo->prepare('SELECT * FROM orders WHERE courier_id=? ORDER BY order_date DESC');$os->execute([$_SESSION['user']['id']]);$orderRows=$os->fetchAll();
    } elseif (($_SESSION['user']['profile'] ?? '') === 'Administrador') {
      $orderRows=$pdo->query('SELECT * FROM orders ORDER BY order_date DESC')->fetchAll();
    } else { $orderRows=[]; }
    $orders=array_map('orderRow',$orderRows);
    $reviews=$pdo->query("SELECT id,product_id,author,rating,comment,review_date FROM reviews ORDER BY review_date DESC")->fetchAll();
    $reviews=array_map(fn($r)=>['id'=>(int)$r['id'],'productId'=>(int)$r['product_id'],'author'=>$r['author'],'rating'=>(int)$r['rating'],'comment'=>$r['comment'],'date'=>date('d/m/Y',strtotime($r['review_date']))],$reviews);
    $coupons=$pdo->query('SELECT code,discount,active FROM coupons WHERE active=1')->fetchAll();
    $coupons=array_map(fn($c)=>['code'=>$c['code'],'discount'=>(float)$c['discount'],'active'=>(bool)$c['active']],$coupons);
    $csrf=$_SESSION['csrf_token']??bin2hex(random_bytes(32)); $_SESSION['csrf_token']=$csrf;
    foreach($products as &$pr){$q=$pdo->prepare('SELECT image_path FROM product_images WHERE product_id=? ORDER BY sort_order,id');$q->execute([$pr['id']]);$pr['images']=array_column($q->fetchAll(),'image_path');$q=$pdo->prepare('SELECT attribute_name,attribute_value FROM product_attributes WHERE product_id=?');$q->execute([$pr['id']]);$pr['attributes']=$q->fetchAll();}
    foreach($orders as &$ord){$q=$pdo->prepare('SELECT status,location_label,notes,latitude,longitude,created_at FROM order_tracking WHERE order_id=(SELECT id FROM orders WHERE folio=?) ORDER BY created_at');$q->execute([$ord['id']]);$ord['tracking']=$q->fetchAll();}
    out(['products'=>$products,'orders'=>$orders,'reviews'=>$reviews,'coupons'=>$coupons,'user'=>$_SESSION['user']??null,'csrfToken'=>$csrf]);
  }

  $d=input();
  if ($action==='register' && $_SERVER['REQUEST_METHOD']==='POST') {
    $name=trim((string)($d['name']??'')); $email=strtolower(trim((string)($d['email']??''))); $pass=(string)($d['password']??'');
    if (mb_strlen($name)<2 || mb_strlen($name)>120 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190 || strlen($pass)<12 || strlen($pass)>200) out(['error'=>'Revisa nombre, correo y contraseña (mínimo 12 caracteres).'],422);
    $ip=$_SERVER['REMOTE_ADDR']??'unknown';
    $lim=$pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE email='__register__' AND ip_address=? AND attempted_at>DATE_SUB(NOW(),INTERVAL 1 HOUR)");$lim->execute([$ip]);
    if((int)$lim->fetchColumn()>=5)out(['error'=>'Se alcanzó el límite de registros desde esta conexión. Intenta más tarde.'],429);
    $pdo->prepare("INSERT INTO login_attempts(email,ip_address) VALUES('__register__',?)")->execute([$ip]);
    $s=$pdo->prepare("INSERT INTO users(name,email,password_hash,profile) VALUES(?,?,?,'Cliente')");
    try {$s->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT)]);} catch(PDOException $e){out(['error'=>'Ya existe una cuenta con ese correo.'],409);}
    session_regenerate_id(true); $_SESSION['csrf_token']=bin2hex(random_bytes(32)); $_SESSION['user']=['id'=>(int)$pdo->lastInsertId(),'name'=>$name,'email'=>$email,'profile'=>'Cliente'];
    out(['user'=>$_SESSION['user'],'csrfToken'=>$_SESSION['csrf_token']]);
  }

  if ($action==='login' && $_SERVER['REQUEST_METHOD']==='POST') {
    $email=strtolower(trim((string)($d['email']??''))); $pass=(string)($d['password']??'');$ip=$_SERVER['REMOTE_ADDR']??'unknown';
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190||$pass===''||strlen($pass)>200)out(['error'=>'Correo o contraseña incorrectos.'],401);
    $pdo->exec("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 2 DAY)");
    $lim=$pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE email=? AND ip_address=? AND attempted_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)');$lim->execute([$email,$ip]);if((int)$lim->fetchColumn()>=5)out(['error'=>'Demasiados intentos. Espera 15 minutos antes de volver a intentar.'],429);
    $s=$pdo->prepare('SELECT id,name,email,password_hash,profile FROM users WHERE email=?');$s->execute([$email]);$u=$s->fetch();
    if(!$u || !password_verify($pass,$u['password_hash'])) {$pdo->prepare('INSERT INTO login_attempts(email,ip_address) VALUES(?,?)')->execute([$email,$ip]);out(['error'=>'Correo o contraseña incorrectos.'],401);}
    $pdo->prepare('DELETE FROM login_attempts WHERE email=? AND ip_address=?')->execute([$email,$ip]);
    session_regenerate_id(true); $_SESSION['csrf_token']=bin2hex(random_bytes(32)); $_SESSION['user']=['id'=>(int)$u['id'],'name'=>$u['name'],'email'=>$u['email'],'profile'=>$u['profile']];
    out(['user'=>$_SESSION['user'],'csrfToken'=>$_SESSION['csrf_token']]);
  }

  if ($action==='logout') { $_SESSION=[]; if(ini_get('session.use_cookies')){ $p=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);} session_destroy();out(['ok'=>true]); }

  if ($action==='create-order' && $_SERVER['REQUEST_METHOD']==='POST') {
    $u=requireRole(['Cliente']); $items=$d['items']??[];
    if(!$items || !is_array($items)) out(['error'=>'El carrito está vacío.'],422);
    $pdo->beginTransaction();
    try {
      $subtotal=0;$normalized=[];$sellerIds=[];
      foreach($items as $item){
        $pid=(int)($item['productId']??0);$qty=(int)($item['qty']??0);
        if($qty<1) throw new RuntimeException('Cantidad inválida.');
        $s=$pdo->prepare('SELECT id,name,price,stock,owner_id FROM products WHERE id=? AND active=1 FOR UPDATE');$s->execute([$pid]);$p=$s->fetch();
        if(!$p || (int)$p['stock']<$qty) throw new RuntimeException('Existencias insuficientes para '.($p['name']??'un producto').'.');
        $sellerIds[]=(int)($p['owner_id']??0);$price=(float)$p['price'];$subtotal += $price*$qty;$normalized[]=['p'=>$p,'qty'=>$qty,'price'=>$price];
      }
      $coupon=null;$discount=0;
      if(!empty($d['coupon'])){$s=$pdo->prepare('SELECT code,discount FROM coupons WHERE code=? AND active=1');$s->execute([strtoupper(trim($d['coupon']))]);$c=$s->fetch();if($c){$coupon=$c['code'];$discount=$subtotal*((float)$c['discount']/100);}}
      $nonzero=array_values(array_unique(array_filter($sellerIds)));
      if(count($nonzero)>1 || (count($nonzero)===1 && in_array(0,$sellerIds,true))) throw new RuntimeException('Para esta compra selecciona productos del mismo vendedor.');
      $sellerId=$nonzero[0]??null;
      $customer=trim((string)($d['customer']??$u['name']));$address=trim((string)($d['address']??''));$payment=(string)($d['payment']??'Pago de prueba');
      if(mb_strlen($customer)<2||mb_strlen($customer)>120||mb_strlen($address)<8||mb_strlen($address)>500||!in_array($payment,['Pago de prueba','Contra entrega'],true))throw new RuntimeException('Revisa los datos de entrega y el método de pago.');
      $total=max(0,$subtotal-$discount);$folio='MT-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(4)));$publicToken=bin2hex(random_bytes(24));$deliveryCode=(string)random_int(100000,999999);
      $s=$pdo->prepare("INSERT INTO orders(folio,user_id,seller_id,public_token,delivery_code_hash,customer,email,address,subtotal,discount,coupon,total,payment,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?, 'Nuevo')");
      $s->execute([$folio,$u['id'],$sellerId,$publicToken,password_hash($deliveryCode,PASSWORD_DEFAULT),trim($d['customer']??$u['name']),$u['email'],$address,$subtotal,$discount,$coupon,$total,$payment]);
      $oid=(int)$pdo->lastInsertId();$ins=$pdo->prepare('INSERT INTO order_items(order_id,product_id,name,qty,price) VALUES(?,?,?,?,?)');
      $upd=$pdo->prepare('UPDATE products SET stock=stock-? WHERE id=?');
      foreach($normalized as $n){$ins->execute([$oid,$n['p']['id'],$n['p']['name'],$n['qty'],$n['price']]);$upd->execute([$n['qty'],$n['p']['id']]);}
      $pdo->prepare("INSERT INTO order_tracking(order_id,status,location_label,notes,created_by) VALUES(?, 'Nuevo','Pedido recibido','Tu pedido fue registrado.',?)")->execute([$oid,$u['id']]);$pdo->commit();
      $s=$pdo->prepare('SELECT * FROM orders WHERE id=?');$s->execute([$oid]);$order=orderRow($s->fetch());$order['deliveryCode']=$deliveryCode;$order['verificationToken']=$publicToken;audit('order.created',$oid,['folio'=>$folio]);out(['order'=>$order]);
    } catch(Throwable $e){$pdo->rollBack();if(!($e instanceof RuntimeException))error_log('M&T create-order: '.$e->getMessage());out(['error'=>$e instanceof RuntimeException?$e->getMessage():'No se pudo registrar el pedido. Inténtalo nuevamente.'],422);}
  }

  if ($action==='order-status' && $_SERVER['REQUEST_METHOD']==='POST') {
    $u=requireRole(['Vendedor','Administrador','Repartidor']);$allowed=['Nuevo','Preparado','Pago confirmado','En preparación','Empacado','Enviado','En camino','Llegando a tu zona','Cerca de tu calle','Entregado','Cancelado'];
    if(!in_array($d['status']??'', $allowed,true))out(['error'=>'Estado inválido'],422);
    $q=$pdo->prepare('SELECT id,seller_id,courier_id,status FROM orders WHERE folio=?');$q->execute([$d['folio']??'']);$ord=$q->fetch();
    if(!$ord)out(['error'=>'Pedido no encontrado'],404);if(in_array($ord['status'],['Entregado','Cancelado'],true))out(['error'=>'El pedido ya está cerrado.'],422);
    if($u['profile']==='Vendedor' && (int)($ord['seller_id']??0)!==(int)$u['id'])out(['error'=>'Este pedido no pertenece a tu cuenta.'],403);if($u['profile']==='Repartidor' && (int)($ord['courier_id']??0)!==(int)$u['id'])out(['error'=>'Este pedido no está asignado a tu cuenta.'],403);if(!canTransitionOrder((string)$ord['status'],(string)$d['status'],(string)$u['profile']))out(['error'=>'La transición de estado no está permitida para tu perfil o para el estado actual.'],422);
    if($d['status']==='Entregado')out(['error'=>'Para entregar el pedido debes validar la clave de recepción.'],422);
    $pdo->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$d['status'],$ord['id']]);
    $pdo->prepare('INSERT INTO order_tracking(order_id,status,location_label,notes,created_by) VALUES(?,?,?,?,?)')->execute([$ord['id'],$d['status'],trim($d['location']??''),trim($d['notes']??''),$u['id']]);audit('order.status',$ord['id'],['status'=>$d['status']]);out(['ok'=>true]);
  }

  if ($action==='save-product' && $_SERVER['REQUEST_METHOD']==='POST') {
    $u=requireRole(['Vendedor','Administrador']);$p=$d['product']??[];$id=(int)($p['id']??0);
    $name=trim($p['name']??'');$price=(float)($p['price']??0);$stock=(int)($p['stock']??0);
    $category=trim((string)($p['category']??'General'));$description=trim((string)($p['description']??''));
    if($name===''||mb_strlen($name)>180||mb_strlen($category)>100||mb_strlen($description)>5000||!is_finite($price)||$price<=0||$price>999999999||$stock<0||$stock>10000000)out(['error'=>'Datos de producto inválidos'],422);
    $pdo->beginTransaction();
    try {
      $values=[$name,trim($p['category']??'General'),$price,($p['oldPrice']??'')===''?null:$p['oldPrice'],$stock,max(0,(int)($p['minStock']??0)),trim($p['image']??''),trim($p['description']??''),!empty($p['active'])?1:0,!empty($p['featured'])?1:0];
      if($id){$q=$pdo->prepare('SELECT id,owner_id,price FROM products WHERE id=? FOR UPDATE');$q->execute([$id]);$existing=$q->fetch();if(!$existing)throw new RuntimeException('Producto no encontrado.');if($u['profile']==='Vendedor' && (int)($existing['owner_id']??0)!==(int)$u['id'])throw new RuntimeException('Solo puedes editar productos asignados a tu cuenta.');
        $sql='UPDATE products SET name=?,category=?,price=?,old_price=?,stock=?,min_stock=?,image=?,description=?,active=?,featured=?,owner_id=COALESCE(owner_id,?) WHERE id=?';$values[]=$u['profile']==='Vendedor'?$u['id']:($existing['owner_id']??null);$values[]=$id;$pdo->prepare($sql)->execute($values);
        $pdo->prepare('INSERT INTO product_price_history(product_id,user_id,old_price,new_price) VALUES(?,?,?,?)')->execute([$id,$u['id'],$existing['price'],$price]);
      } else {$values[]=$u['profile']==='Vendedor'?$u['id']:null;$pdo->prepare('INSERT INTO products(name,category,price,old_price,stock,min_stock,image,description,active,featured,owner_id) VALUES(?,?,?,?,?,?,?,?,?,?,?)')->execute($values);$id=(int)$pdo->lastInsertId();}
      $pdo->prepare('DELETE FROM product_attributes WHERE product_id=?')->execute([$id]);$attr=$pdo->prepare('INSERT INTO product_attributes(product_id,attribute_name,attribute_value) VALUES(?,?,?)');foreach(($p['attributes']??[]) as $a){$an=trim($a['name']??'');$av=trim($a['value']??'');if($an!==''&&$av!=='')$attr->execute([$id,mb_substr($an,0,80),mb_substr($av,0,255)]);}
      $pdo->commit();audit('product.saved',$id);out(['ok'=>true,'id'=>$id]);
    } catch(Throwable $e){$pdo->rollBack();error_log('M&T save-product: '.$e->getMessage());out(['error'=>$e instanceof RuntimeException?$e->getMessage():'No se pudo guardar el producto.'],422);}
  }

  if ($action==='upload-product-images' && $_SERVER['REQUEST_METHOD']==='POST') {
    $u=requireRole(['Vendedor','Administrador']);$pid=(int)($_POST['productId']??0);$q=$pdo->prepare('SELECT id,owner_id FROM products WHERE id=?');$q->execute([$pid]);$product=$q->fetch();if(!$product)out(['error'=>'Producto no encontrado'],404);
    if($u['profile']==='Vendedor' && (int)($product['owner_id']??0)!==(int)$u['id'])out(['error'=>'No puedes modificar este producto.'],403);
    if(empty($_FILES['images']))out(['error'=>'Selecciona al menos una imagen.'],422);
    $dir=dirname(__DIR__).'/uploads/products';if(!is_dir($dir)&&!mkdir($dir,0750,true))out(['error'=>'No se pudo crear el directorio de imágenes.'],500);
    $f=$_FILES['images'];$count=is_array($f['name'])?count($f['name']):1;if($count>8)out(['error'=>'Máximo 8 imágenes por carga.'],422);$saved=[];
    for($i=0;$i<$count;$i++){ $tmp=is_array($f['tmp_name'])?$f['tmp_name'][$i]:$f['tmp_name'];$err=is_array($f['error'])?$f['error'][$i]:$f['error'];$size=is_array($f['size'])?$f['size'][$i]:$f['size'];if($err!==UPLOAD_ERR_OK||$size>5*1024*1024)out(['error'=>'Imagen inválida o mayor a 5 MB.'],422);$info=@getimagesize($tmp);if(!$info||$info[0]<1||$info[1]<1||$info[0]>6000||$info[1]>6000||($info[0]*$info[1])>20000000)out(['error'=>'Imagen inválida o con dimensiones excesivas.'],422);$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;if(!$ext||$mime!==($info['mime']??''))out(['error'=>'Solo se permiten JPG, PNG y WEBP.'],422);$filename=bin2hex(random_bytes(18)).'.'.$ext;if(!move_uploaded_file($tmp,$dir.'/'.$filename))out(['error'=>'No se pudo guardar una imagen.'],500);$path='/mt-tienda-virtual/uploads/products/'.$filename;$pdo->prepare('INSERT INTO product_images(product_id,image_path,uploaded_by) VALUES(?,?,?)')->execute([$pid,$path,$u['id']]);$saved[]=$path;if($i===0){$pdo->prepare('UPDATE products SET image=? WHERE id=?')->execute([$path,$pid]);$product['image']=$path;}}
    audit('product.images',$pid,['count'=>count($saved)]);out(['ok'=>true,'images'=>$saved]);
  }

  if ($action==='delivery-staff' && $_SERVER['REQUEST_METHOD']==='GET') {
    requireRole(['Vendedor','Administrador']);$q=$pdo->query("SELECT id,name,email FROM users WHERE profile='Repartidor' ORDER BY name");out(['couriers'=>$q->fetchAll()]);
  }
  if ($action==='assign-courier' && $_SERVER['REQUEST_METHOD']==='POST') {
    $u=requireRole(['Vendedor','Administrador']);$folio=trim($d['folio']??'');$cid=(int)($d['courierId']??0);$q=$pdo->prepare('SELECT id,seller_id,status FROM orders WHERE folio=?');$q->execute([$folio]);$o=$q->fetch();if(!$o)out(['error'=>'Pedido no encontrado'],404);if(in_array($o['status'],['Entregado','Cancelado'],true))out(['error'=>'El pedido ya está cerrado.'],422);if($u['profile']==='Vendedor'&&(int)($o['seller_id']??0)!==(int)$u['id'])out(['error'=>'Pedido ajeno'],403);$q=$pdo->prepare("SELECT id FROM users WHERE id=? AND profile='Repartidor'");$q->execute([$cid]);if(!$q->fetch())out(['error'=>'La cuenta seleccionada no es repartidor.'],422);$pdo->prepare('UPDATE orders SET courier_id=? WHERE id=?')->execute([$cid,$o['id']]);audit('order.courier.assigned',$o['id'],['courierId'=>$cid]);out(['ok'=>true]);
  }

  if ($action==='order-track' && $_SERVER['REQUEST_METHOD']==='POST') {
    $u=requireRole(['Vendedor','Administrador','Repartidor']);$folio=trim($d['folio']??'');$q=$pdo->prepare('SELECT id,seller_id,courier_id,status FROM orders WHERE folio=?');$q->execute([$folio]);$o=$q->fetch();if(!$o)out(['error'=>'Pedido no encontrado'],404);if($u['profile']==='Vendedor'&&(int)($o['seller_id']??0)!==(int)$u['id'])out(['error'=>'Pedido ajeno'],403);if($u['profile']==='Repartidor'&&(int)($o['courier_id']??0)!==(int)$u['id'])out(['error'=>'Pedido no asignado'],403);if(in_array($o['status'],['Entregado','Cancelado'],true))out(['error'=>'El pedido ya no admite cambios de seguimiento.'],422);
    $status=trim($d['status']??'En camino');if(!in_array($status,['Nuevo','Preparado','Pago confirmado','En preparación','Empacado','Enviado','En camino','Llegando a tu zona','Cerca de tu calle'],true))out(['error'=>'Estado de seguimiento inválido.'],422);if(!canTransitionOrder((string)$o['status'],$status,(string)$u['profile']))out(['error'=>'No puedes realizar ese cambio de estado.'],422);$location=trim((string)($d['location']??''));$notes=trim((string)($d['notes']??''));if(mb_strlen($location)>255||mb_strlen($notes)>1000)out(['error'=>'La ubicación o las notas superan el límite permitido.'],422);if(isset($d['latitude'])||isset($d['longitude'])){if(!is_numeric($d['latitude']??null)||!is_numeric($d['longitude']??null)||(float)$d['latitude'] < -90||(float)$d['latitude'] > 90||(float)$d['longitude'] < -180||(float)$d['longitude'] > 180)out(['error'=>'Coordenadas GPS inválidas.'],422);}$pdo->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status,$o['id']]);$pdo->prepare('INSERT INTO order_tracking(order_id,status,location_label,notes,latitude,longitude,created_by) VALUES(?,?,?,?,?,?,?)')->execute([$o['id'],$status,$location,$notes,$d['latitude']??null,$d['longitude']??null,$u['id']]);if(isset($d['latitude'],$d['longitude']))$pdo->prepare('INSERT INTO delivery_locations(order_id,courier_id,latitude,longitude) VALUES(?,?,?,?)')->execute([$o['id'],$u['profile']==='Repartidor'?$u['id']:null,$d['latitude'],$d['longitude']]);audit('order.tracking',$o['id'],['status'=>$status]);out(['ok'=>true]);
  }

  if ($action==='confirm-delivery' && $_SERVER['REQUEST_METHOD']==='POST') {
    $u=requireRole(['Vendedor','Administrador','Repartidor']);$folio=trim($d['folio']??'');$code=(string)($d['code']??'');$q=$pdo->prepare('SELECT id,seller_id,courier_id,status,delivery_code_hash,delivery_attempts,delivery_locked_until FROM orders WHERE folio=? FOR UPDATE');$pdo->beginTransaction();$q->execute([$folio]);$o=$q->fetch();if(!$o){$pdo->rollBack();out(['error'=>'Pedido no encontrado'],404);}if($u['profile']==='Vendedor'&&(int)($o['seller_id']??0)!==(int)$u['id']){$pdo->rollBack();out(['error'=>'Pedido ajeno'],403);}if($u['profile']==='Repartidor'&&(int)($o['courier_id']??0)!==(int)$u['id']){$pdo->rollBack();out(['error'=>'Pedido no asignado'],403);}if(in_array($o['status'],['Entregado','Cancelado'],true)||empty($o['delivery_code_hash'])){$pdo->rollBack();out(['error'=>'El pedido ya está cerrado o la clave fue utilizada.'],422);}if(!empty($o['delivery_locked_until'])&&strtotime($o['delivery_locked_until'])>time()){$pdo->rollBack();out(['error'=>'La validación está bloqueada temporalmente. Intenta en 15 minutos.'],429);}if(!preg_match('/^[0-9]{6}$/',$code)||!password_verify($code,$o['delivery_code_hash'])){$pdo->prepare('UPDATE orders SET delivery_attempts=delivery_attempts+1,delivery_locked_until=IF(delivery_attempts+1>=5,DATE_ADD(NOW(),INTERVAL 15 MINUTE),delivery_locked_until) WHERE id=?')->execute([$o['id']]);$pdo->commit();out(['error'=>'Clave de recepción incorrecta.'],422);}
    $pdo->prepare("UPDATE orders SET status='Entregado',delivery_confirmed_at=NOW(),delivery_confirmed_by=? WHERE id=?")->execute([$u['id'],$o['id']]);$pdo->prepare("INSERT INTO order_tracking(order_id,status,location_label,notes,created_by) VALUES(?,'Entregado','Entrega confirmada','Recepción validada mediante clave.',?)")->execute([$o['id'],$u['id']]);$pdo->prepare('UPDATE orders SET delivery_code_hash=NULL,delivery_attempts=0,delivery_locked_until=NULL WHERE id=?')->execute([$o['id']]);$pdo->commit();audit('order.delivered',$o['id']);out(['ok'=>true]);
  }

  if ($action==='seller-returns' && $_SERVER['REQUEST_METHOD']==='GET') {
    $u=requireRole(['Vendedor','Administrador']);$sql='SELECT r.id,r.kind,r.reason,r.status,r.seller_response,r.low_value,r.created_at,o.folio,p.name AS product_name,u.name AS customer FROM returns r JOIN orders o ON o.id=r.order_id JOIN products p ON p.id=r.product_id JOIN users u ON u.id=r.user_id';
    if($u['profile']==='Vendedor'){$sql.=' WHERE o.seller_id=?';$q=$pdo->prepare($sql.' ORDER BY r.created_at DESC');$q->execute([$u['id']]);}else{$q=$pdo->query($sql.' ORDER BY r.created_at DESC');}
    $rows=$q->fetchAll();foreach($rows as &$row){$ev=$pdo->prepare('SELECT id FROM return_evidence WHERE return_id=?');$ev->execute([$row['id']]);$row['evidence']=array_column($ev->fetchAll(),'id');}out(['returns'=>$rows]);
  }
  if ($action==='my-returns' && $_SERVER['REQUEST_METHOD']==='GET') {
    $u=requireRole(['Cliente']);$q=$pdo->prepare('SELECT r.id,r.kind,r.reason,r.status,r.seller_response,r.low_value,r.created_at,o.folio,p.name AS product_name FROM returns r JOIN orders o ON o.id=r.order_id JOIN products p ON p.id=r.product_id WHERE r.user_id=? ORDER BY r.created_at DESC');$q->execute([$u['id']]);$rows=$q->fetchAll();foreach($rows as &$row){$ev=$pdo->prepare('SELECT id FROM return_evidence WHERE return_id=?');$ev->execute([$row['id']]);$row['evidence']=array_column($ev->fetchAll(),'id');}out(['returns'=>$rows]);
  }
  if ($action==='verify-order' && $_SERVER['REQUEST_METHOD']==='GET') {
    $token=(string)($_GET['token']??'');$q=$pdo->prepare('SELECT folio,status,order_date FROM orders WHERE public_token=?');$q->execute([$token]);$o=$q->fetch();if(!$o)out(['valid'=>false],404);out(['valid'=>true,'folio'=>$o['folio'],'status'=>$o['status'],'date'=>$o['order_date']]);
  }

  if ($action==='return-request' && $_SERVER['REQUEST_METHOD']==='POST') {
    $u=requireRole(['Cliente']);$folio=trim($d['folio']??'');$pid=(int)($d['productId']??0);$reason=trim($d['reason']??'');$kind=in_array($d['kind']??'',['Devolución','Garantía','Cambio'],true)?$d['kind']:'Devolución';if($reason===''||mb_strlen($reason)>2000)out(['error'=>'Describe el motivo (máximo 2,000 caracteres).'],422);
    $q=$pdo->prepare("SELECT o.id,i.price FROM orders o JOIN order_items i ON i.order_id=o.id WHERE o.folio=? AND o.user_id=? AND i.product_id=? AND o.status='Entregado'");$q->execute([$folio,$u['id'],$pid]);$purchase=$q->fetch();if(!$purchase)out(['error'=>'No se encontró ese producto recibido en tus compras.'],403);
    $limit=(float)(getenv('MT_LOW_VALUE_RETURN_LIMIT')?:1000);$lowValue=((float)$purchase['price']<=$limit)?1:0;$pdo->prepare("INSERT INTO returns(order_id,product_id,user_id,kind,reason,status,low_value) VALUES(?,?,?,?,?,'Pendiente',?)")->execute([$purchase['id'],$pid,$u['id'],$kind,$reason,$lowValue]);$rid=(int)$pdo->lastInsertId();if($kind==='Garantía')$pdo->prepare("INSERT INTO warranties(return_id,warranty_type,status) VALUES(?,'Garantía de producto','Abierta')")->execute([$rid]);audit('return.requested',$rid,['lowValue'=>$lowValue]);out(['ok'=>true,'id'=>$rid]);
  }

  if ($action==='upload-return-evidence' && $_SERVER['REQUEST_METHOD']==='POST') {
    $u=requireRole(['Cliente']);$rid=(int)($_POST['returnId']??0);$q=$pdo->prepare('SELECT id FROM returns WHERE id=? AND user_id=?');$q->execute([$rid,$u['id']]);if(!$q->fetch())out(['error'=>'Solicitud no encontrada'],404);if(empty($_FILES['evidence']))out(['error'=>'Selecciona una imagen.'],422);
    $dir=dirname(__DIR__).'/private_uploads/returns';if(!is_dir($dir)&&!mkdir($dir,0750,true))out(['error'=>'No se pudo crear el directorio.'],500);$f=$_FILES['evidence'];$n=is_array($f['name'])?count($f['name']):1;if($n>5)out(['error'=>'Máximo 5 evidencias por carga.'],422);$saved=[];
    for($i=0;$i<$n;$i++){$tmp=is_array($f['tmp_name'])?$f['tmp_name'][$i]:$f['tmp_name'];$err=is_array($f['error'])?$f['error'][$i]:$f['error'];$size=is_array($f['size'])?$f['size'][$i]:$f['size'];if($err!==UPLOAD_ERR_OK||$size>5*1024*1024)out(['error'=>'Archivo inválido o mayor a 5 MB.'],422);$info=@getimagesize($tmp);if(!$info||$info[0]<1||$info[1]<1||$info[0]>6000||$info[1]>6000||($info[0]*$info[1])>20000000)out(['error'=>'Imagen inválida o con dimensiones excesivas.'],422);$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;if(!$ext||$mime!==($info['mime']??''))out(['error'=>'Solo se permiten imágenes JPG, PNG o WEBP.'],422);$fn=bin2hex(random_bytes(18)).'.'.$ext;if(!move_uploaded_file($tmp,$dir.'/'.$fn))out(['error'=>'No se pudo guardar la evidencia.'],500);$path=$fn;$pdo->prepare('INSERT INTO return_evidence(return_id,file_path,uploaded_by) VALUES(?,?,?)')->execute([$rid,$path,$u['id']]);$saved[]=$path;}
    out(['ok'=>true,'files'=>$saved]);
  }

  if ($action==='view-return-evidence' && $_SERVER['REQUEST_METHOD']==='GET') {
    $u=requireRole(['Vendedor','Administrador']);$eid=(int)($_GET['id']??0);$q=$pdo->prepare('SELECT e.file_path,o.seller_id FROM return_evidence e JOIN returns r ON r.id=e.return_id JOIN orders o ON o.id=r.order_id WHERE e.id=?');$q->execute([$eid]);$ev=$q->fetch();if(!$ev)out(['error'=>'Evidencia no encontrada'],404);if($u['profile']==='Vendedor'&&(int)($ev['seller_id']??0)!==(int)$u['id'])out(['error'=>'No autorizado'],403);$path=dirname(__DIR__).'/private_uploads/returns/'.basename($ev['file_path']);if(!is_file($path))out(['error'=>'Archivo no disponible'],404);$mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);if(!in_array($mime,['image/jpeg','image/png','image/webp'],true))out(['error'=>'Tipo de archivo no permitido'],415);header('Content-Type: '.$mime);header('Content-Disposition: inline; filename="evidencia-'.$eid.'"');header('X-Content-Type-Options: nosniff');readfile($path);exit;
  }

  if ($action==='return-decision' && $_SERVER['REQUEST_METHOD']==='POST') {
    $u=requireRole(['Vendedor','Administrador']);$id=(int)($d['id']??0);$status=in_array($d['status']??'',['Aprobada','Rechazada','En revisión','Resuelta'],true)?$d['status']:'';if(!$status)out(['error'=>'Estado inválido'],422);$q=$pdo->prepare('SELECT r.id,o.seller_id FROM returns r JOIN orders o ON o.id=r.order_id WHERE r.id=?');$q->execute([$id]);$r=$q->fetch();if(!$r)out(['error'=>'Solicitud no encontrada'],404);if($u['profile']==='Vendedor'&&(int)($r['seller_id']??0)!==(int)$u['id'])out(['error'=>'Solicitud ajena'],403);$response=trim($d['response']??'');$pdo->prepare('UPDATE returns SET status=?,seller_response=?,reviewed_by=?,updated_at=NOW() WHERE id=?')->execute([$status,$response,$u['id'],$id]);if($status==='Resuelta')$pdo->prepare("UPDATE warranties SET status='Cerrada',resolution=? WHERE return_id=?")->execute([$response,$id]);elseif($status==='Rechazada')$pdo->prepare("UPDATE warranties SET status='Rechazada',resolution=? WHERE return_id=?")->execute([$response,$id]);audit('return.decision',$id,['status'=>$status]);out(['ok'=>true]);
  }

  if ($action==='review' && $_SERVER['REQUEST_METHOD']==='POST') {
    $u=requireRole(['Cliente']);$pid=(int)($d['productId']??0);$rating=(int)($d['rating']??0);$comment=trim($d['comment']??'');
    if($rating<1||$rating>5||$comment===''||mb_strlen($comment)>2000)out(['error'=>'Reseña inválida'],422);
    $q=$pdo->prepare("SELECT COUNT(*) FROM orders o JOIN order_items i ON i.order_id=o.id WHERE o.user_id=? AND o.status='Entregado' AND i.product_id=?");$q->execute([$u['id'],$pid]);if(!(int)$q->fetchColumn())out(['error'=>'Solo puedes reseñar productos que hayas recibido.'],403);$q=$pdo->prepare('SELECT id FROM reviews WHERE product_id=? AND user_id=?');$q->execute([$pid,$u['id']]);if($q->fetch())out(['error'=>'Ya publicaste una reseña para este producto.'],409);
    $s=$pdo->prepare('INSERT INTO reviews(product_id,user_id,author,rating,comment) VALUES(?,?,?,?,?)');$s->execute([$pid,$u['id'],trim($u['name']),$rating,$comment]);audit('review.created',$pid);
    $pdo->prepare('UPDATE products SET reviews=reviews+1,rating=(SELECT avg_rating FROM (SELECT AVG(rating) avg_rating FROM reviews WHERE product_id=?) x) WHERE id=?')->execute([$pid,$pid]);
    out(['ok'=>true]);
  }

  if ($action==='reserve' && $_SERVER['REQUEST_METHOD']==='POST') {
    $ip=$_SERVER['REMOTE_ADDR']??'unknown';$lim=$pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE email='__reserve__' AND ip_address=? AND attempted_at>DATE_SUB(NOW(),INTERVAL 1 HOUR)");$lim->execute([$ip]);if((int)$lim->fetchColumn()>=10)out(['error'=>'Demasiadas solicitudes de apartado. Intenta más tarde.'],429);
    $name=trim((string)($d['name']??''));$phone=trim((string)($d['phone']??''));$pid=(int)($d['productId']??0);$days=(string)($d['days']??'2 días');
    if(mb_strlen($name)<2||mb_strlen($name)>120||!preg_match('/^[0-9+() .-]{8,20}$/',$phone)||!in_array($days,['1 día','2 días','3 días','5 días'],true))out(['error'=>'Datos de apartado inválidos.'],422);
    $pcheck=$pdo->prepare('SELECT id FROM products WHERE id=? AND active=1');$pcheck->execute([$pid]);if(!$pcheck->fetch())out(['error'=>'Producto no disponible.'],404);
    $pdo->prepare("INSERT INTO login_attempts(email,ip_address) VALUES('__reserve__',?)")->execute([$ip]);
    $folio='RES-'.strtoupper(bin2hex(random_bytes(3)));$s=$pdo->prepare('INSERT INTO reservations(folio,product_id,customer_name,phone,days) VALUES(?,?,?,?,?)');
    $s->execute([$folio,$pid,$name,$phone,$days]);audit('reservation.created',(int)$pdo->lastInsertId());out(['folio'=>$folio]);
  }

  out(['error'=>'Acción no encontrada'],404);
} catch(Throwable $e) {
  error_log('M&T API: '.$e->getMessage());out(['error'=>'Error interno del servidor. Revisa el registro de PHP.'],500);
}
