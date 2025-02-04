<script src="/vendor/CesiumUnminified/Cesium.js"></script>
<link rel="stylesheet" href="/vendor/CesiumUnminified/Widgets/widgets.css" />

<style>
    html,
    body,
    #cmap {
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
        overflow: hidden;
    }
</style>

<div id="cmap"></div>

<script>
    window.CESIUM_BASE_URL = '/vendor/CesiumUnminified';
    Cesium.Ion.defaultAccessToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiIxNDczMzZmOS1kNDgxLTRkOGQtYTY0Mi0xMzVjNjZiZTdjNmQiLCJpZCI6MjczMjUwLCJpYXQiOjE3Mzg2NTY1NTB9.A5VQBmzdB-kyb75qWpyC4Q5iO8WOARHFqeiE_hjksz0';
    const viewer = new Cesium.Viewer('cmap');
</script>

