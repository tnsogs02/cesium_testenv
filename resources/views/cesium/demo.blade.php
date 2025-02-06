<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="/js/vendor/CesiumUnminified/Cesium.js"></script>
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
        width: 40%;
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
    <button data-bind="click: uploadWaypoints">upload</button>
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
                <td><input type="number" data-bind="textInput: height" style="width: 4rem;"></td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    window.CESIUM_BASE_URL = '/js/vendor/CesiumUnminified';
    Cesium.Ion.defaultAccessToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiIxNDczMzZmOS1kNDgxLTRkOGQtYTY0Mi0xMzVjNjZiZTdjNmQiLCJpZCI6MjczMjUwLCJpYXQiOjE3Mzg2NTY1NTB9.A5VQBmzdB-kyb75qWpyC4Q5iO8WOARHFqeiE_hjksz0';
    const viewer = new Cesium.Viewer('viewer');
    const pinBuilder = new Cesium.PinBuilder();

    function roundToPrecision(num, precision) {
        const factor = Math.pow(10, precision);
        return Math.round(num * factor) / factor;
    }

    let handler;
    let currentCoordinate;
    let waypointsArray = ko.observableArray();
    let path;
    ko.extenders.numeric = function(target, precision) {
        let result = ko.pureComputed({
            read: target,
            write: function(newValue) {
                let current = target();
                let valueToWrite = parseFloat(newValue) || 0;
                valueToWrite = roundToPrecision(valueToWrite, precision);
                if (valueToWrite !== current) {
                    target(valueToWrite);
                }
            }
        }).extend({notify: 'always'});

        result(target());

        return result;
    }


    let WaypointViewModel = function() {
        let self = this;
        self.waypointsArray = ko.observableArray();

        self.addWaypoint = function(longitude, latitude, height, billboardId) {
            waypointViewModel.waypointsArray.push({
                longitude: roundToPrecision(longitude, 6),
                latitude: roundToPrecision(latitude, 6),
                height: ko.observable(height).extend({numeric: 2}),
                billboard_id: billboardId
            });
        }

        self.getWaypointByBillboardId = function(billboardId) {
            const waypoint = self.waypointsArray().filter(item => item.billboard_id === billboardId)[0];
            return {
                order: self.waypointsArray().indexOf(waypoint),
                longitude: waypoint.longitude,
                latitude: waypoint.latitude,
                height: waypoint.height(),
                billboard_id: waypoint.billboard_id
            }
        }

        self.uploadWaypoints = function() {
            $.ajax({
                type: "POST",
                url: "{{ route('cesium.waypoints_add') }}",
                data: {
                    _token: '{{ csrf_token() }}',
                    waypoints: self.waypointsArray().map(waypoint => {
                        return {
                            order: self.waypointsArray().indexOf(waypoint),
                            longitude: waypoint.longitude,
                            latitude: waypoint.latitude,
                            height: waypoint.height(),
                        }
                    })
                },
                success: function(data) {
                    console.log(data);
                }
            })




        }
    }

    let waypointViewModel = new WaypointViewModel();
    ko.applyBindings(waypointViewModel, document.getElementById('toolbox'));

    function renderPath(){
        if (path) {
            viewer.entities.remove(path);
        }
        if (waypointViewModel.waypointsArray().length > 1) {
            const waypointsRenderArray = [];
            waypointViewModel.waypointsArray().forEach(waypoint => {
                waypointsRenderArray.push(
                    waypoint.longitude,
                    waypoint.latitude,
                    waypoint.height()
                );
            });

            waypointsRenderArray.push(
                waypointViewModel.waypointsArray()[0].longitude,
                waypointViewModel.waypointsArray()[0].latitude,
                waypointViewModel.waypointsArray()[0].height()
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
        Swal.fire({
            title: "Clear all waypoints?",
            showCancelButton: true,
            confirmButtonText: "Process",
        }).then((result) => {
            if (result.isConfirmed) {
                viewer.entities.removeAll();
                waypointViewModel.waypointsArray.removeAll();
            }
        });
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

            waypointViewModel.addWaypoint(longitude, latitude, height, billboard.id);
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
                            waypointViewModel.waypointsArray.remove(function (waypointsArray) {
                                return waypointsArray.billboard_id === billboardId;
                            });
                            viewer.entities.remove(viewer.entities.getById(billboardId));
                        }
                    }
                    break;
                case 'R':
                case 'r':
                    renderPath();
                    break;

                case 'C':
                case 'c':
                    clearAll();
                    break;
            }
        }
    );

    viewer.selectedEntityChanged.addEventListener(function(selectedEntity) {
        if (selectedEntity) {
            if (selectedEntity.billboard) {
                const billboard = viewer.entities.getById(selectedEntity.id);
                const waypoint = waypointViewModel.getWaypointByBillboardId(selectedEntity.id);
                billboard.description = `Waypoint ${waypoint.order}(${waypoint.longitude.toFixed(6)}, ${waypoint.latitude.toFixed(6)}, ${waypoint.height.toFixed(2)})`;
            }
        }
    });

</script>

