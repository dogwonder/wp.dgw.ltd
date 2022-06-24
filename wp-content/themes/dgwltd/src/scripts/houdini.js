//Via - https://css-tricks.com/creating-generative-patterns-with-the-css-paint-api/
import random from "https://cdn.skypack.dev/random";
import seedrandom from "https://cdn.skypack.dev/seedrandom";

class TinySpecksPattern {
  static get inputProperties() {
    return [
      "--pattern-seed",
      "--pattern-colors",
      "--pattern-speck-count",
      "--pattern-speck-min-size",
      "--pattern-speck-max-size"
    ];
  }

  paint(ctx, geometry, props) {
    const { width, height } = geometry;

    const seed = props.get("--pattern-seed").value;
    const colors = props.getAll("--pattern-colors");
    const count = props.get("--pattern-speck-count").value;
    const minSize = props.get("--pattern-speck-min-size").value;
    const maxSize = props.get("--pattern-speck-max-size").value;

    random.use(seedrandom(seed));

    for (let i = 0; i < count; i++) {
      const x = random.float(0, width);
      const y = random.float(0, height);
      const radius = random.float(minSize, maxSize);

      ctx.fillStyle = colors[random.int(0, colors.length - 1)];

      ctx.save();

      ctx.translate(x, y);
      ctx.rotate(((random.float(0, 360) * 180) / Math.PI) * 2);
      ctx.translate(-x, -y);

      triangle(ctx, x, y, radius);
      ctx.fill();

      // ctx.beginPath();
      // ctx.ellipse(x, y, radius, radius / 2, 0, Math.PI * 2, 0);
      // ctx.fill();

      ctx.restore();
    }
  }
}

function triangle(ctx, cx, cy, size) {
  const originX = cx - size / 2;
  const originY = cy - size / 2;
  ctx.beginPath();
  ctx.moveTo(originX, originY);
  ctx.lineTo(originX + size, originY + size);
  ctx.lineTo(originX, originY + size);
  ctx.closePath();
}

if (typeof registerPaint !== "undefined") {
  registerPaint("tinySpecksPattern", TinySpecksPattern);
}