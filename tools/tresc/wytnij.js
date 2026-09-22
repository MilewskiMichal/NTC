// Wycina prostokąt z obrazka przez canvas w Chromium - w kontenerze nie ma
// ani PIL, ani ImageMagick.
const { chromium } = require('/opt/node22/lib/node_modules/playwright');
const fs = require('fs');
(async () => {
  const [wej, wyj, x, y, w, h, jakosc] = process.argv.slice(2);
  const b = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium-1194/chrome-linux/chrome', args: ['--no-sandbox'] });
  const p = await b.newPage();
  const dane = 'data:image/png;base64,' + fs.readFileSync(wej).toString('base64');
  const wynik = await p.evaluate(async ([src, x, y, w, h, q]) => {
    const img = new Image(); img.src = src; await img.decode();
    const c = document.createElement('canvas'); c.width = w; c.height = h;
    c.getContext('2d').drawImage(img, x, y, w, h, 0, 0, w, h);
    return { d: c.toDataURL('image/jpeg', q), nw: img.naturalWidth, nh: img.naturalHeight };
  }, [dane, +x, +y, +w, +h, +(jakosc || 0.92)]);
  fs.writeFileSync(wyj, Buffer.from(wynik.d.split(',')[1], 'base64'));
  console.log('źródło', wynik.nw + 'x' + wynik.nh, '-> wycinek', w + 'x' + h);
  await b.close();
})();
