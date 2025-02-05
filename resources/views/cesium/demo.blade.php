<script src="/vendor/CesiumUnminified/Cesium.js"></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/knockout/3.5.0/knockout-min.js'></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        padding-left: 0.5rem;
        top: 5px;
        left: 5px;
        width: 35%;
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
                <td><input type="text" data-bind="textInput: height" style="width: 3.5rem;"></td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    window.CESIUM_BASE_URL = '/vendor/CesiumUnminified';
    Cesium.Ion.defaultAccessToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiIxNDczMzZmOS1kNDgxLTRkOGQtYTY0Mi0xMzVjNjZiZTdjNmQiLCJpZCI6MjczMjUwLCJpYXQiOjE3Mzg2NTY1NTB9.A5VQBmzdB-kyb75qWpyC4Q5iO8WOARHFqeiE_hjksz0';
    const viewer = new Cesium.Viewer('viewer');
    const pinBuilder = new Cesium.PinBuilder();

    let handler;
    let currentCoordinate;
    let waypointsArray = ko.observableArray();
    let path;

    ko.applyBindings({ waypointsArray }, document.getElementById('toolbox'));

    function renderPath(){
        if (path) {
            viewer.entities.remove(path);
        }
        if (waypointsArray().length > 1) {
            const waypointsRenderArray = [];
            waypointsArray().forEach(waypoint => {
                waypointsRenderArray.push(
                    waypoint.longitude,
                    waypoint.latitude,
                    waypoint.height
                );
            });

            waypointsRenderArray.push(
                waypointsArray()[0].longitude,
                waypointsArray()[0].latitude,
                waypointsArray()[0].height
            );

            path = viewer.entities.add({
                polyline: {
                    positions: Cesium.Cartesian3.fromDegreesArrayHeights(waypointsRenderArray),
                    width: 5,
                    material: Cesium.Color.GREEN,
                    clampToGround: false,
                },
            });
        }
    }

    function clearAll() {
        viewer.entities.removeAll();
        waypointsArray.removeAll();
    }

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
        currentCoordinate = viewer.camera.pickEllipsoid(
            movement.endPosition,
            viewer.scene.globe.ellipsoid,
        );
        if (currentCoordinate) {
            coordBox.position = currentCoordinate;
            const cartographic = Cesium.Cartographic.fromCartesian(currentCoordinate);
            const longitudeString = Cesium.Math.toDegrees(cartographic.longitude).toFixed(6);
            const latitudeString = Cesium.Math.toDegrees(cartographic.latitude).toFixed(6);
            coordBox.label.text = `${longitudeString}\n${latitudeString}`;
            coordBox.label.show = true;
        } else {
            coordBox.label.show = false;
        }
    }, Cesium.ScreenSpaceEventType.MOUSE_MOVE);

    handler.setInputAction(position => {
        if (currentCoordinate) {
            const cartographic = Cesium.Cartographic.fromCartesian(currentCoordinate);
            const longitude = Cesium.Math.toDegrees(cartographic.longitude);
            const latitude = Cesium.Math.toDegrees(cartographic.latitude);
            const height = 1500;
            const billboard = viewer.entities.add({
                position: currentCoordinate,
                billboard: {
                    image: pinBuilder.fromColor(Cesium.Color.BLUE, 48).toDataURL(),
                    verticalOrigin: Cesium.VerticalOrigin.BOTTOM,
                }
            })

            waypointsArray.push({
                longitude: longitude,
                latitude: latitude,
                height: height,
                billboard_id: billboard.id
            });
        }
    }, Cesium.ScreenSpaceEventType.RIGHT_CLICK);

    document.addEventListener(
        "keydown",
        (event) => {
            switch (event.key) {
                case "Backspace":
                    if(viewer.selectedEntity) {
                        if(viewer.selectedEntity.billboard){
                            const billboardId = viewer.selectedEntity.id;
                            waypointsArray.remove(function (waypointsArray) {
                                return waypointsArray.billboard_id === billboardId;
                            });
                            viewer.entities.remove(viewer.entities.getById(billboardId));
                        }
                    }
                    break;
                case 'r':
                case 'R':
                    renderPath();
                    break;

                case 'c':
                case 'C':
                    Swal.fire({
                        title: "Clear all waypoints?",
                        showCancelButton: true,
                        confirmButtonText: "Process",
                    }).then((result) => {
                        if (result.isConfirmed) {
                            clearAll();
                        }
                    });
                    break;
            }
        }
    );

    viewer.selectedEntityChanged.addEventListener(function(selectedEntity) {
        if (selectedEntity) {
            if (selectedEntity.billboard) {
                const waypoint = waypointsArray().filter(item => item.billboard_id === selectedEntity.id)[0];
                const billboard = viewer.entities.getById(selectedEntity.id);
                billboard.description = `Waypoint ${waypointsArray.indexOf(waypoint)}(${waypoint.longitude.toFixed(6)}, ${waypoint.latitude.toFixed(6)}, ${waypoint.height.toFixed(2)})`;
            }
        }
    });

</script>

