<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>貓咪抓魚</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        background: #001833;
        overflow: hidden;
        user-select: none;
        font-family: "Microsoft JhengHei", sans-serif;
        display: flex;
        flex-direction: column;
    }
    .top-bar {
        width: 100%;
        background: rgba(0,0,20,.7);
        padding: 10px 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        flex-shrink: 0;
        z-index: 10;
    }
    .top-bar a { color: #aaccff; text-decoration: none; font-size: 16px; font-weight: bold; }
    .top-bar a:hover { color: #fff; }
    .top-bar .page-title { color: #fff; font-size: 17px; font-weight: bold; }

    #gameCanvas { display: block; cursor: crosshair; flex-shrink: 0; }

    #ui {
        position: fixed;
        top: 52px;
        left: 16px;
        z-index: 10;
        color: #fff;
        font-size: 20px;
        text-shadow: 0 2px 6px rgba(0,0,0,.7);
        pointer-events: none;
    }
    #restartBtn {
        pointer-events: auto;
        padding: 10px 22px;
        font-size: 15px;
        background: #ffd700;
        color: #333;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        display: none;
        margin-top: 10px;
        font-family: "Microsoft JhengHei", sans-serif;
        font-weight: bold;
    }
    #winMsg {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #fff;
        font-size: 30px;
        font-weight: bold;
        text-align: center;
        text-shadow: 0 4px 10px rgba(0,0,0,.8);
        display: none;
        pointer-events: none;
    }
    #timerBar {
        position: fixed;
        bottom: 0; left: 0;
        height: 5px;
        background: #ffd700;
        width: 100%;
        transform-origin: left;
        transition: transform linear;
    }
</style>
</head>
<body>

<div class="top-bar">
    <a href="game_select.php">← 返回</a>
    <span class="page-title">🐱 貓咪抓魚</span>
</div>

<div id="ui">
    抓到：<span id="score">0</span> / 15 隻<br>
    時間：<span id="timeLeft">15</span>s
    <br><button id="restartBtn" onclick="initGame()">🔄 重新開始</button>
</div>
<div id="winMsg" id="winMsg"></div>
<div id="timerBar"></div>

<canvas id="gameCanvas"></canvas>

<script>
const canvas    = document.getElementById("gameCanvas");
const ctx       = canvas.getContext("2d");
const topBarH   = document.querySelector('.top-bar').offsetHeight;
const scoreEl   = document.getElementById("score");
const timeEl    = document.getElementById("timeLeft");
const restartBtn = document.getElementById("restartBtn");
const winMsg    = document.getElementById("winMsg");
const timerBar  = document.getElementById("timerBar");

function resize() {
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight - topBarH;
}
resize();
window.addEventListener("resize", resize);

let fishes = [], particles = [], score = 0;
let timeLeft = 15, gameRunning = false;
let timerInterval = null;

/* ── 建立一條魚 ── */
function createFish() {
    const goRight = Math.random() > .5;
    return {
        x:    goRight ? -40 : canvas.width + 40,
        y:    60 + Math.random() * (canvas.height - 120),
        size: 16 + Math.random() * 14,
        dx:   (goRight ? 1 : -1) * (1.2 + Math.random() * 2),
        dy:   (Math.random() - .5) * 1.2,
        hue:  Math.floor(Math.random() * 360),
    };
}

/* ── 初始化遊戲 ── */
function initGame() {
    fishes = [];
    particles = [];
    for (let i = 0; i < 15; i++) fishes.push(createFish());
    score = 0;
    timeLeft = 15;
    scoreEl.textContent = 0;
    timeEl.textContent  = 15;
    restartBtn.style.display = "none";
    winMsg.style.display     = "none";
    timerBar.style.transform = "scaleX(1)";
    timerBar.style.transition = `transform ${timeLeft}s linear`;
    gameRunning = true;

    clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (!gameRunning) return;
        timeLeft--;
        timeEl.textContent = timeLeft;
        if (timeLeft <= 0) endGame(false);
    }, 1000);

    // start bar shrink
    requestAnimationFrame(() => {
        timerBar.style.transform = "scaleX(0)";
    });
}

/* ── 結束遊戲 ── */
function endGame(won) {
    gameRunning = false;
    clearInterval(timerInterval);
    restartBtn.style.display = "inline-block";
    winMsg.innerHTML = won
        ? `🎉 全部抓完！<br><span style="font-size:22px;color:#ffd700">共抓到 ${score} 隻！</span>`
        : `⏰ 時間到！<br><span style="font-size:22px;color:#ffd700">抓到 ${score} / 15 隻</span>`;
    winMsg.style.display = "block";
}

/* ── 點擊判定 ── */
canvas.addEventListener("mousedown", e => {
    if (!gameRunning) return;
    const rect = canvas.getBoundingClientRect();
    const mx = e.clientX - rect.left;
    const my = e.clientY - rect.top;
    for (let i = fishes.length - 1; i >= 0; i--) {
        const f = fishes[i];
        if (Math.hypot(mx - f.x, my - f.y) < f.size + 8) {
            spawnParticles(f.x, f.y, f.hue);
            fishes.splice(i, 1);
            score++;
            scoreEl.textContent = score;
            if (fishes.length === 0) endGame(true);
            break;
        }
    }
});

canvas.addEventListener("touchstart", e => {
    if (!gameRunning) return;
    e.preventDefault();
    const rect = canvas.getBoundingClientRect();
    const t = e.touches[0];
    const mx = t.clientX - rect.left;
    const my = t.clientY - rect.top;
    for (let i = fishes.length - 1; i >= 0; i--) {
        const f = fishes[i];
        if (Math.hypot(mx - f.x, my - f.y) < f.size + 16) {
            spawnParticles(f.x, f.y, f.hue);
            fishes.splice(i, 1);
            score++;
            scoreEl.textContent = score;
            if (fishes.length === 0) endGame(true);
            break;
        }
    }
}, { passive: false });

/* ── 粒子 ── */
function spawnParticles(x, y, hue) {
    for (let i = 0; i < 10; i++) {
        particles.push({
            x, y,
            dx: (Math.random() - .5) * 7,
            dy: (Math.random() - .5) * 7,
            life: 22,
            hue,
        });
    }
}

/* ── 畫魚 ── */
function drawFish(f) {
    ctx.save();
    ctx.translate(f.x, f.y);
    if (f.dx < 0) ctx.scale(-1, 1);

    // body
    ctx.fillStyle = `hsl(${f.hue},80%,60%)`;
    ctx.beginPath();
    ctx.ellipse(0, 0, f.size, f.size * .55, 0, 0, Math.PI * 2);
    ctx.fill();

    // tail
    ctx.beginPath();
    ctx.moveTo(-f.size, 0);
    ctx.lineTo(-f.size - f.size * .65, -f.size * .52);
    ctx.lineTo(-f.size - f.size * .65,  f.size * .52);
    ctx.closePath();
    ctx.fill();

    // belly highlight
    ctx.fillStyle = `hsla(${f.hue},80%,80%,.5)`;
    ctx.beginPath();
    ctx.ellipse(f.size * .1, f.size * .15, f.size * .45, f.size * .28, 0, 0, Math.PI * 2);
    ctx.fill();

    // eye white
    ctx.fillStyle = "#fff";
    ctx.beginPath();
    ctx.arc(f.size * .6, -f.size * .12, f.size * .18, 0, Math.PI * 2);
    ctx.fill();
    // pupil
    ctx.fillStyle = "#222";
    ctx.beginPath();
    ctx.arc(f.size * .64, -f.size * .12, f.size * .09, 0, Math.PI * 2);
    ctx.fill();

    ctx.restore();
}

/* ── 主迴圈 ── */
function gameLoop() {
    // background
    const bg = ctx.createLinearGradient(0, 0, 0, canvas.height);
    bg.addColorStop(0, "#001833");
    bg.addColorStop(1, "#003366");
    ctx.fillStyle = bg;
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // decorative bubbles
    ctx.fillStyle = "rgba(80,160,255,.04)";
    for (let i = 0; i < 6; i++) {
        ctx.beginPath();
        ctx.arc(canvas.width * (i + .5) / 6, canvas.height * .75, 30 + i * 12, 0, Math.PI * 2);
        ctx.fill();
    }

    // update & draw fish
    fishes.forEach(f => {
        f.x += f.dx;
        f.y += f.dy;
        if (f.dx > 0 && f.x > canvas.width + 50) f.x = -50;
        if (f.dx < 0 && f.x < -50)               f.x = canvas.width + 50;
        if (f.y < 0 || f.y > canvas.height)       f.dy *= -1;
        drawFish(f);
    });

    // particles
    particles = particles.filter(p => p.life > 0);
    particles.forEach(p => {
        ctx.fillStyle = `hsla(${p.hue},90%,65%,${p.life / 22})`;
        ctx.beginPath();
        ctx.arc(p.x, p.y, 4 * (p.life / 22), 0, Math.PI * 2);
        ctx.fill();
        p.x += p.dx; p.y += p.dy; p.life--;
    });

    requestAnimationFrame(gameLoop);
}

initGame();
gameLoop();
</script>
</body>
</html>
