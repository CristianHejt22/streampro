import https from 'https'

function requestTua(url, body = '') {
  return new Promise((resolve, reject) => {
    const u = new URL(url)
    const req = https.request({
      hostname: u.hostname,
      path: u.pathname + u.search,
      method: body ? 'POST' : 'GET',
      headers: {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko)',
        'Content-Type': 'application/x-www-form-urlencoded',
        'Content-Length': Buffer.byteLength(body),
        'Accept': 'application/json, text/plain, */*'
      }
    }, res => {
      let data = ''
      res.on('data', chunk => data += chunk)
      res.on('end', () => resolve(data))
    })
    req.on('error', reject)
    if (body) req.write(body)
    req.end()
  })
}

function parseArgentinePrice(val) {
  if (typeof val === 'number') return val
  if (!val) return 0
  let str = String(val).replace(/\$/g, '').trim()
  if (str.includes(',')) {
    str = str.split('.').join('').replace(',', '.')
  } else {
    const dotCount = (str.match(/\./g) || []).length
    if (dotCount > 1) {
      str = str.split('.').join('')
    } else if (dotCount === 1 && str.split('.')[1].length === 3) {
      str = str.split('.').join('')
    }
  }
  str = str.replace(/[^0-9.]/g, '')
  const num = parseFloat(str)
  return isNaN(num) ? 0 : num
}

export default async function handler(req, res) {
  // Configuración de encabezados CORS y cache
  res.setHeader('Access-Control-Allow-Origin', '*')
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type')

  if (req.method === 'OPTIONS') {
    return res.status(200).end()
  }

  try {
    const rawCats = await requestTua('https://tua.ar/catalogo.php?accion=categorias')
    const cats = JSON.parse(rawCats)
    const catMap = {}
    cats.forEach(c => { catMap[c.id] = c.name })

    const prodMap = new Map()
    for (const c of cats) {
      if (parseInt(c.cantidad || '0') <= 0) continue
      let page = 0
      while (page < 10) {
        const body = `page=${page}&order=&category=${c.id}&buscar=`
        const data = await requestTua('https://tua.ar/catalogo.php', body)
        if (!data || !data.trim()) break
        let list = []
        try {
          list = JSON.parse(data)
        } catch (e) {
          break
        }
        if (!list || list.length === 0) break

        list.forEach(item => {
          const categoryName = catMap[c.id] || 'General'
          const imageClean = item.image 
            ? (item.image.startsWith('http') ? item.image : `https://tua.ar/${item.image.replace(/^\//, '')}`)
            : 'https://tua.ar/images/banner22.webp'
          const priceNum = parseArgentinePrice(item.price)

          if (!prodMap.has(item.id)) {
            prodMap.set(item.id, {
              ...item,
              category: categoryName,
              categoryIds: [c.id],
              image: imageClean,
              priceNumeric: priceNum,
              stock: item.stock || 20
            })
          } else {
            const existing = prodMap.get(item.id)
            if (!existing.categoryIds.includes(c.id)) {
              existing.categoryIds.push(c.id)
            }
          }
        })

        if (list.length < 10) break
        page++
      }
    }

    const allProducts = Array.from(prodMap.values()).sort((a, b) => parseInt(b.id) - parseInt(a.id))
    const cleanCategories = cats.filter(c => parseInt(c.cantidad || '0') > 0)

    return res.status(200).json({
      success: true,
      timestamp: new Date().toISOString(),
      count: allProducts.length,
      categoriesCount: cleanCategories.length,
      categories: cleanCategories,
      products: allProducts
    })
  } catch (error) {
    return res.status(500).json({
      success: false,
      error: error.message || 'Error al conectar y sincronizar con tua.ar'
    })
  }
}
