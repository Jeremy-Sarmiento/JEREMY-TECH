import requests
import os
import json
import time

class DescargadorIShop:
    def __init__(self, tienda_url='https://pe.tiendasishop.com', carpeta_salida='imagenes_ishop'):
        self.tienda_url = tienda_url.rstrip('/')
        self.carpeta_salida = os.path.abspath(carpeta_salida)
        self.headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept': 'application/json'
        }
        os.makedirs(self.carpeta_salida, exist_ok=True)
    
    def obtener_todos_los_productos(self):
        """Obtiene todos los productos usando la paginación de Shopify"""
        productos = []
        page = 1
        limit = 250
        
        print("Obteniendo catálogo de productos de iShop...")
        while True:
            url = f"{self.tienda_url}/products.json?page={page}&limit={limit}"
            try:
                response = requests.get(url, headers=self.headers, timeout=30)
                response.raise_for_status()
                data = response.json()
                page_products = data.get('products', [])
                if not page_products:
                    break
                productos.extend(page_products)
                print(f"  Página {page}: Obtenidos {len(page_products)} productos...")
                page += 1
                time.sleep(0.5)
            except Exception as e:
                print(f"  Error obteniendo página {page}: {e}")
                break
                
        print(f"Total de productos en catálogo: {len(productos)}")
        return productos
    
    def descargar_imagen(self, url, ruta_destino):
        """Descarga una imagen individual"""
        try:
            response = requests.get(url, headers=self.headers, timeout=30, stream=True)
            response.raise_for_status()
            with open(ruta_destino, 'wb') as f:
                for chunk in response.iter_content(chunk_size=8192):
                    f.write(chunk)
            return True
        except Exception as e:
            print(f"  Error al descargar imagen {url}: {e}")
            return False
            
    def limpiar_nombre(self, nombre):
        """Limpia caracteres no permitidos en nombres de carpetas"""
        caracteres_invalidos = '<>:"/\\|?*'
        for char in caracteres_invalidos:
            nombre = nombre.replace(char, '_')
        return nombre.strip()
        
    def procesar(self):
        productos = self.obtener_todos_los_productos()
        if not productos:
            print("No se pudieron obtener productos de la tienda.")
            return
            
        # Filtrar por iPhone 15 Pro o iPhone 15
        filtrados = [p for p in productos if "iphone 15 pro" in p['title'].lower()]
        
        if not filtrados:
            print("No se encontraron productos específicos de 'iPhone 15 Pro'.")
            print("Buscando cualquier producto que contenga 'iPhone 15'...")
            filtrados = [p for p in productos if "iphone 15" in p['title'].lower()]
            
        if not filtrados:
            print("No se encontraron productos de 'iPhone 15'.")
            print("Buscando cualquier 'iPhone'...")
            filtrados = [p for p in productos if "iphone" in p['title'].lower()]
            
        if not filtrados:
            print("No se encontró ningún iPhone. Descargando los primeros 10 productos disponibles de la tienda...")
            filtrados = productos[:10]
            
        print(f"\nSe procederá a descargar {len(filtrados)} productos encontrados:")
        for p in filtrados:
            print(f"  - {p['title']}")
            
        total_descargado = 0
        for producto in filtrados:
            nombre_limpio = self.limpiar_nombre(producto['title'])
            dir_producto = os.path.join(self.carpeta_salida, nombre_limpio)
            os.makedirs(dir_producto, exist_ok=True)
            
            # Guardar información del producto
            info_path = os.path.join(dir_producto, 'info.json')
            with open(info_path, 'w', encoding='utf-8') as f:
                json.dump(producto, f, ensure_ascii=False, indent=2)
                
            print(f"\nDescargando imágenes de: {producto['title']}")
            for idx, img in enumerate(producto.get('images', [])):
                src = img.get('src')
                if src:
                    # Forzar alta resolución si es posible
                    if '?' in src:
                        src_full = src.split('?')[0] + '?width=2000'
                    else:
                        src_full = src + '?width=2000'
                        
                    ext = '.jpg'
                    if '.png' in src_full.lower():
                        ext = '.png'
                    elif '.webp' in src_full.lower():
                        ext = '.webp'
                        
                    filename = f"imagen_{idx+1:02d}{ext}"
                    filepath = os.path.join(dir_producto, filename)
                    
                    if self.descargar_imagen(src_full, filepath):
                        print(f"  ✓ {filename} descargada.")
                        total_descargado += 1
                    else:
                        # Reintento con URL original si falla la de alta resolución
                        print(f"  Reintentando con URL original...")
                        if self.descargar_imagen(src, filepath):
                            print(f"  ✓ {filename} descargada (URL original).")
                            total_descargado += 1
                            
        print(f"\nProceso finalizado. Total de imágenes descargadas: {total_descargado}")
        print(f"Guardadas en: {self.carpeta_salida}")

if __name__ == '__main__':
    descargador = DescargadorIShop()
    descargador.procesar()
