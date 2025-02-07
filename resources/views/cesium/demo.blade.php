{{--
    todo list:當mouse hover on #toolbox時不允許render或remove waypoint
--}}

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="/vendor/CesiumUnminified/Cesium.js"></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/knockout/3.5.0/knockout-min.js'></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="/vendor/CesiumUnminified/Widgets/widgets.css" />
<link rel="stylesheet" href="/css/cesium.css" />

<div id="viewer"></div>

<div id="toolbox">
    <div style="display: flex;">
        <button data-bind="click: syncWaypoints">Sync</button>
        <button data-bind="click: removeSelectedWaypoints">Remove Selected</button>
        <label>Default Height:<input type="number" data-bind="textInput: defaultHeight" style="width: 4rem;"></label>
    </div>
    <table>
        <thead>
            <tr>
                <td><input type="checkbox" data-bind="checked: selectAllWaypoints"></td>
                <th>No.</th>
                <th>Longtitude</th>
                <th>Latitude</th>
                <th>Height</th>
            </tr>
        </thead>
        <tbody data-bind="foreach: waypointsArray">
            <tr>
                <td><input type="checkbox" data-bind="checked: isSelected"></td>
                <td data-bind="text: $index"></td>
                <td data-bind="text: longitude"></td>
                <td data-bind="text: latitude"></td>
                <td><input type="number" data-bind="textInput: height" style="width: 4rem;"></td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    function roundToPrecision(num, precision) {
        const factor = Math.pow(10, precision);
        return Math.round(num * factor) / factor;
    }

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

    window.CESIUM_BASE_URL = '/vendor/CesiumUnminified';
    Cesium.Ion.defaultAccessToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiIxNDczMzZmOS1kNDgxLTRkOGQtYTY0Mi0xMzVjNjZiZTdjNmQiLCJpZCI6MjczMjUwLCJpYXQiOjE3Mzg2NTY1NTB9.A5VQBmzdB-kyb75qWpyC4Q5iO8WOARHFqeiE_hjksz0';
    const viewer = new Cesium.Viewer('viewer', {
        terrain: Cesium.Terrain.fromWorldTerrain()
    });
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
    const pinBuilder = new Cesium.PinBuilder();
    const handler = new Cesium.ScreenSpaceEventHandler(viewer.canvas);
    let timerAutoRenderPath;
    let currentCoordinate;
    let path;

    let WaypointViewModel = function() {
        let self = this;
        self.waypointsArray = ko.observableArray();
        self.defaultHeight = ko.observable(100).extend({numeric: 2});
        self.selectAllWaypoints = ko.observable(false);

        self.selectAllWaypoints.subscribe(function(value) {
            self.waypointsArray().forEach(waypoint => {
                waypoint.isSelected(value);
            });
        });

        self.selectWaypointByBillboardId = function (billboardId) {
            self.waypointsArray().forEach(waypoint => {
                if(waypoint.billboard_id === billboardId) {
                    waypoint.isSelected(true);
                } else {
                    waypoint.isSelected(false);
                }
            });
        }

        self.addWaypoint = function(longitude, latitude, height = self.defaultHeight(), billboardId = null) {
            if(billboardId === null) {
                const billboard = viewer.entities.add({
                    position: Cesium.Cartesian3.fromDegrees(longitude, latitude, height),
                    billboard: {
                        image: pinBuilder.fromText(self.waypointsArray().length, Cesium.Color.BLUE, 48).toDataURL(),
                        verticalOrigin: Cesium.VerticalOrigin.BOTTOM,
                    }
                });
                billboardId = billboard.id;
            }
            self.waypointsArray.push({
                longitude: roundToPrecision(longitude, 6),
                latitude: roundToPrecision(latitude, 6),
                height: ko.observable(height).extend({numeric: 2}),
                isSelected: ko.observable(false),
                billboard_id: billboardId
            });
        }

        self.getWaypointInfoByBillboardId = function(billboardId) {
            const waypoint = self.waypointsArray().filter(item => item.billboard_id === billboardId)[0];
            return {
                order: self.waypointsArray().indexOf(waypoint),
                longitude: waypoint.longitude,
                latitude: waypoint.latitude,
                height: waypoint.height(),
                billboard_id: waypoint.billboard_id
            }
        }

        self.loadWaypointsFromRemote = function() {
            $.ajax({
                type: "GET",
                url: "{{ route('cesium.waypoints_get') }}",
                success: function(data) {
                    self.clearAll(true);
                    data.waypoints.forEach(waypoint => {
                        self.addWaypoint(waypoint.longitude, waypoint.latitude, waypoint.height);
                    });
                    self.renderPath();
                }
            })
        }

        self.syncWaypoints = function() {
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
                    if(data.status === "success") {
                        self.loadWaypointsFromRemote();
                        swal.fire({
                            icon: 'success',
                            title: 'Waypoints synced successfully',
                            showConfirmButton: true,
                            timer: 5000
                        })
                    } else {
                        swal.fire({
                            icon: 'error',
                            title: 'Waypoints sync failed',
                            text: data.description,
                            showConfirmButton: true,
                        })
                    }
                }
            })
        }

        self.renderPath = function () {
            if (path) {
                viewer.entities.remove(path);
            }
            if (self.waypointsArray().length > 1) {
                const waypointsRenderArray = [];
                self.waypointsArray().forEach(waypoint => {
                    waypointsRenderArray.push(
                        waypoint.longitude,
                        waypoint.latitude,
                        waypoint.height()
                    );
                });

                waypointsRenderArray.push(
                    self.waypointsArray()[0].longitude,
                    self.waypointsArray()[0].latitude,
                    self.waypointsArray()[0].height()
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

        self.reloadWaypointBillboards = function () {
            viewer.entities.removeAll();
            self.waypointsArray().forEach(waypoint => {
                const billboard = viewer.entities.add({
                    position: Cesium.Cartesian3.fromDegrees(waypoint.longitude, waypoint.latitude, waypoint.height()),
                    billboard: {
                        image: pinBuilder.fromText(self.waypointsArray().indexOf(waypoint), Cesium.Color.BLUE, 48).toDataURL(),
                        verticalOrigin: Cesium.VerticalOrigin.BOTTOM,
                    }
                });
                waypoint.billboard_id = billboard.id;
            });
        }

        self.clearAll = function (disableWarning=false) {
            if (!disableWarning) {
                Swal.fire({
                    title: "Clear all waypoints?",
                    showCancelButton: true,
                    confirmButtonText: "Process",
                }).then((result) => {
                    if (result.isConfirmed) {
                        viewer.entities.removeAll();
                        self.waypointsArray.removeAll();
                    }
                });
            } else {
                viewer.entities.removeAll();
                self.waypointsArray.removeAll();
            }
        }

        self.removeSelectedWaypoints = function () {
            Swal.fire({
                title: "Remove selected waypoints?",
                showCancelButton: true,
                confirmButtonText: "Process",
            }).then((result) => {
                if (result.isConfirmed) {
                    self.waypointsArray.remove(function (waypoint) {
                        return waypoint.isSelected() === true;
                    });
                    self.reloadWaypointBillboards();
                    self.renderPath();
                }
            });
        }
    }

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

    document.addEventListener(
        "keydown",
        (event) => {
            switch (event.key) {
                case "Backspace":
                    waypointViewModel.removeSelectedWaypoints();
                    break;
                case 'A':
                case 'a':
                    if (currentCoordinate) {
                        if(timerAutoRenderPath){
                            clearTimeout(timerAutoRenderPath);
                        }
                        timerAutoRenderPath = setTimeout(() => {
                            waypointViewModel.renderPath();
                        }, 500);
                        const cartographic = Cesium.Cartographic.fromCartesian(currentCoordinate);
                        const longitude = Cesium.Math.toDegrees(cartographic.longitude);
                        const latitude = Cesium.Math.toDegrees(cartographic.latitude);
                        const height = cartographic.height;
                        waypointViewModel.addWaypoint(longitude, latitude);
                    }
                    break;
            }
        }
    );

    viewer.selectedEntityChanged.addEventListener(function(selectedEntity) {
        if (selectedEntity) {
            if (selectedEntity.billboard) {
                const billboard = viewer.entities.getById(selectedEntity.id);
                const waypointInfo = waypointViewModel.getWaypointInfoByBillboardId(selectedEntity.id);
                waypointViewModel.selectWaypointByBillboardId(billboard.id);
                billboard.description = `Waypoint ${waypointInfo.order}(${waypointInfo.longitude}, ${waypointInfo.latitude}, ${waypointInfo.height})`;
            }
        }
    });

    let waypointViewModel = new WaypointViewModel();
    ko.applyBindings(waypointViewModel, document.getElementById('toolbox'));

    waypointViewModel.loadWaypointsFromRemote();
</script>

