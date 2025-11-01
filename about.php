<?php
// Prestige Perfumery - Maison (About Page)
session_start();
require_once('includes/config.php');
require_once('includes/header.php');
?>

<!-- Load Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500&family=Playfair+Display:wght@400;500&display=swap" rel="stylesheet">

<section class="maison-section">
    <div class="maison-container">
        <h1 class="lux-title">Maison Prestige</h1>
        <p class="lux-subtitle">Crafting scents that transcend time</p>

        <div class="maison-content">
            <p>
                Founded on the philosophy that fragrance is an art form, 
                <strong>Prestige Perfumery</strong> creates timeless scents that embody elegance, emotion, and individuality. 
                Each bottle is meticulously crafted — from the first note to the last — using only the most refined and rare ingredients sourced from around the world.
            </p>

            <p>
                Our mission is to redefine modern luxury by blending classic perfumery traditions with contemporary design. 
                We believe that scent is not merely worn — it’s experienced. 
                Every fragrance we create tells a story, inviting you to indulge in a world where sophistication meets soul.
            </p>

            <p>
                Whether you are discovering your first signature scent or expanding your collection, 
                <em>Prestige Perfumery</em> welcomes you to a house where art, memory, and beauty intertwine — 
                the true essence of a Maison.
            </p>

            <a href="/prestigeperfumes/items/index.php" class="btn"><span>Explore Our Collection</span></a>
        </div>
    </div>
</section>

<?php require_once('includes/footer.php'); ?>

<style>
body {
    margin: 0;
    font-family: 'Montserrat', sans-serif;
    background: #ffffff;
    color: #0a0a0a;
}

/* Section Layout */
.maison-section {
    padding: 120px 20px;
    min-height: 85vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.maison-container {
    max-width: 900px;
    text-align: center;
}

/* Headings */
.lux-title {
    font-family: 'Playfair Display', serif;
    font-size: 42px;
    font-weight: 400;
    letter-spacing: 1px;
    color: #0a0a0a;
    margin-bottom: 10px;
}

.lux-subtitle {
    font-family: 'Montserrat', sans-serif;
    font-size: 14px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: rgba(0,0,0,0.6);
    margin-bottom: 50px;
}

/* Content */
.maison-content {
    font-family: 'Montserrat', sans-serif;
    font-weight: 300;
    color: #333;
    line-height: 1.8;
    font-size: 15px;
    text-align: justify;
}

.maison-content p {
    margin-bottom: 1.8rem;
}

/* BUTTON (Same Theme as Header + Cart) */
.btn {
    display: inline-block;
    margin-top: 20px;
    padding: 14px 40px;
    text-decoration: none;
    font-family: 'Montserrat', sans-serif;
    font-size: 12px;
    letter-spacing: 2px;
    text-transform: uppercase;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    font-weight: 400;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    border: 1px solid rgba(0, 0, 0, 0.3);
    background: transparent;
    color: #0a0a0a;
    min-width: 200px;
    text-align: center;
}

.btn span {
    position: relative;
    z-index: 2;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: #0a0a0a;
    transition: left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1;
}

.btn:hover::before {
    left: 0;
}

.btn:hover {
    color: #ffffff;
    border-color: #0a0a0a;
}

/* Responsive */
@media (max-width: 768px) {
    .lux-title {
        font-size: 32px;
    }

    .maison-content {
        text-align: left;
    }
}
</style>
