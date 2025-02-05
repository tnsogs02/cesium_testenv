<script src="/vendor/CesiumUnminified/Cesium.js"></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/knockout/3.5.0/knockout-min.js'></script>
<link rel="stylesheet" href="/vendor/CesiumUnminified/Widgets/widgets.css" />

<style>
    #cmap {
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
        overflow: hidden;
    }

    #toolbox {
        position: absolute;
        top: 5px;
        left: 5px;
        width: 30%;
        height: 20%;
        background-color: white;
        overflow: scroll;
    }
    #toolbox th, #toolbox td {
        padding-right: 0.5rem;
    }
</style>


<div id="viewer"></div>

<div id="toolbox">
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Longtitude</th>
                <th>Latitude</th>
                <th>Height</th>
            </tr>
        </thead>
        <tbody data-bind="foreach: waypointsArray">
            <tr>
                <td data-bind="text: $index"></td>
                <td data-bind="text: longitude"></td>
                <td data-bind="text: latitude"></td>
                <td><input type="text" data-bind="textInput: height" style="width: 2rem;"></td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    window.CESIUM_BASE_URL = '/vendor/CesiumUnminified';
    Cesium.Ion.defaultAccessToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiIxNDczMzZmOS1kNDgxLTRkOGQtYTY0Mi0xMzVjNjZiZTdjNmQiLCJpZCI6MjczMjUwLCJpYXQiOjE3Mzg2NTY1NTB9.A5VQBmzdB-kyb75qWpyC4Q5iO8WOARHFqeiE_hjksz0';
    const viewer = new Cesium.Viewer('viewer');
    const scene = viewer.scene;
    if (!scene.pickPositionSupported) {
        window.alert("This browser does not support pickPosition.");
    }
    const pinBuilder = new Cesium.PinBuilder();

    let handler;
    let cartesian;
    let waypointsArray = ko.observableArray();

    ko.applyBindings({ waypointsArray }, document.getElementById('toolbox'));

    const coordBox = viewer.entities.add({
        label: {
            show: false,
            showBackground: true,
            font: "14px monospace",
            horizontalOrigin: Cesium.HorizontalOrigin.LEFT,
            verticalOrigin: Cesium.VerticalOrigin.TOP,
            pixelOffset: new Cesium.Cartesian2(15, 0),
        },
    });

    handler = new Cesium.ScreenSpaceEventHandler(viewer.canvas);
    handler.setInputAction(movement => {
        cartesian = viewer.camera.pickEllipsoid(
            movement.endPosition,
            scene.globe.ellipsoid,
        );
        if (cartesian) {
            coordBox.position = cartesian;
            const cartographic = Cesium.Cartographic.fromCartesian(cartesian);
            const longitudeString = Cesium.Math.toDegrees(cartographic.longitude).toFixed(5);
            const latitudeString = Cesium.Math.toDegrees(cartographic.latitude).toFixed(5);
            coordBox.label.text = `${longitudeString}\n${latitudeString}`;
            coordBox.label.show = true;
        } else {
            coordBox.label.show = false;
        }
    }, Cesium.ScreenSpaceEventType.MOUSE_MOVE);

    handler.setInputAction(position => {
        if (cartesian) {
            const cartographic = Cesium.Cartographic.fromCartesian(cartesian);
            const longitudeString = Cesium.Math.toDegrees(cartographic.longitude).toFixed(5);
            const latitudeString = Cesium.Math.toDegrees(cartographic.latitude).toFixed(5);
            waypointsArray.push({
                longitude: longitudeString,
                latitude: latitudeString,
                height: 50
            });
            viewer.entities.add({
                position: cartesian,
                billboard: {
                    image: pinBuilder.fromColor(Cesium.Color.ROYALBLUE, 48).toDataURL(),
                    verticalOrigin: Cesium.VerticalOrigin.BOTTOM,
                }
            })
        }
    }, Cesium.ScreenSpaceEventType.LEFT_CLICK);

</script>

