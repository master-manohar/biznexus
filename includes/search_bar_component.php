<?php
/**
 * includes/search_bar_component.php
 * Universal Premium Search Bar for BizNexus Platform.
 */
?>
<style>
    .bn-search-container {
        max-width: 800px;
        margin: 20px auto;
        position: relative;
        z-index: 10;
        padding: 0 15px;
    }
    .bn-search-box {
        background: rgba(19, 19, 26, 0.8);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 215, 0, 0.2);
        border-radius: 50px;
        padding: 6px 10px;
        display: flex;
        align-items: center;
        box-shadow: 0 10px 40px rgba(0,0,0,0.4), 0 0 20px rgba(255, 215, 0, 0.05);
        transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .bn-search-box:focus-within {
        border-color: #FFD700;
        box-shadow: 0 15px 50px rgba(0,0,0,0.5), 0 0 30px rgba(255, 215, 0, 0.15);
        transform: translateY(-2px);
    }
    .bn-search-input-group {
        display: flex;
        flex: 1;
        align-items: center;
    }
    .bn-search-field {
        background: transparent;
        border: none;
        color: #fff;
        padding: 12px 20px;
        font-size: 1rem;
        width: 100%;
        outline: none;
    }
    .bn-search-field::placeholder {
        color: #666;
    }
    .bn-city-field {
        background: transparent;
        border: none;
        border-left: 1px solid rgba(255, 255, 255, 0.1);
        color: #FFD700;
        padding: 12px 20px;
        font-size: 0.95rem;
        max-width: 150px;
        outline: none;
        font-weight: 500;
    }
    .bn-search-btn {
        background: #FFD700;
        color: #000;
        border: none;
        border-radius: 40px;
        padding: 10px 28px;
        font-weight: 800;
        font-family: 'Syne', sans-serif;
        font-size: 0.9rem;
        cursor: pointer;
        transition: 0.2s;
        white-space: nowrap;
        margin-left: 8px;
    }
    .bn-search-btn:hover {
        background: #fff;
        transform: scale(1.03);
    }
    @media(max-width: 576px) {
        .bn-search-box {
            flex-direction: column;
            border-radius: 20px;
            padding: 15px;
        }
        .bn-city-field {
            border-left: none;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 100%;
            width: 100%;
        }
        .bn-search-btn {
            width: 100%;
            margin-left: 0;
            margin-top: 10px;
            padding: 14px;
        }
    }
</style>

<div class="bn-search-container">
    <form action="/find.php" method="GET" class="bn-search-box">
        <div class="bn-search-input-group">
            <input type="text" name="q" class="bn-search-field" placeholder="Search for Products, Services or Companies..." required autocomplete="off">
            <input type="text" name="city" class="bn-city-field" placeholder="All India" autocomplete="off">
        </div>
        <button type="submit" class="bn-search-btn">FIND NOW</button>
    </form>
</div>
