<template>
    <div id="recorder">
        <div class="content">
            <header class="title wrap">
                <h1 class="title_main text-center">Media Stream Recorder
                    <span class="title_sub">by&nbsp;Quickblox</span>
                </h1>
            </header>

            <aside class="toast wrap">
                This demo requires Firefox&nbsp;49, Chrome&nbsp;49, Chrome&nbsp;for&nbsp;Android&nbsp;53, Opera&nbsp;41 or&nbsp;later. The https is required.
            </aside>

            <main>
                <div class="columns wrap">
                    <div class="column col-6 col-xs-12">
                        <div class="card j-card">
                            <div class="card-image">
                                <div class="video-responsive">
                                    <video class=" j-video_local video" controls muted></video>
                                </div>
                            </div>

                            <div class="source">
                                <div class="form-group">
                                    <select id="j-audioSource" class="invisible form-select select-sm">
                                    </select>
                                </div>

                                <div class="form-group">
                                    <select id="j-videoSource" class="invisible form-select select-sm">
                                    </select>
                                </div>
                            </div>

                            <div class="card-footer">
                                <h5 class="card-title">Recording</h5>
                                <div class="btn-group btn-group-block">
                                    <button class="btn j-start">Start</button>
                                    <button class="btn j-pause" disabled>Pause</button>
                                    <button class="btn j-resume" disabled>Resume</button>
                                    <button class="btn j-stop" disabled>Stop</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="column col-6 col-xs-12">
                        <div class="card j-result-card">
                            <div class="card-image">
                                <div class="video-responsive">
                                    <video class="j-video_result video" controls></video>
                                </div>
                            </div>

                            <div class="card-footer">
                                <h5 class="card-title">Record</h5>
                                <div class="btn-group btn-group-block">
                                    <button class="btn j-clear" disabled>Clear</button>
                                    <button class="btn j-download" disabled>Download</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
<style>
    html {
        overflow-y: scroll;
        height: 100%;
    }

    body {
        min-height: 100%;
        display: flex;
        flex-direction: column;
    }

    .content {
        flex: 1;
    }

    .wrap {
        width: 80%;
        max-width: 1120px;
        margin: 0 auto;
    }

    .title_main {
        font-weight: 700;
    }

    .title_sub {
        font-size: .6em;
        font-weight: 300;
    }

    .source {
        width: 100%;

        position: absolute;
        top: 5px;
        left: 5px;
    }

    .video {
        background-color: #efefef;
    }

    .video-responsive .video {
        height: 100%;
        left: 0;
        position: absolute;
        top: 0;
        width: 100%;
    }

    .footer {
        padding: 1em 0;

        background: #efefef;
        text-align: center;
    }

    .footer_desc {
        width: 80%;
        margin: 0 auto;

    }

    .notify {
        opacity: 0;

        max-width: 480px;
        position: fixed;
        bottom: 10%;
        right: 5%;

        transition: opacity .4s ease;
    }

    .notify-active {
        opacity: 1;
    }

    /* Changes for spectre */
    .form-select {
        max-width: 100%;
    }

    .card {
        position: relative;
    }

    .form-select:not([multiple]) {
        background-color: rgba(255, 255, 255, .4);
    }
</style>
<script>

    export default {
        mounted() {
            /* JSHint inline rules */
            /* jshint node: true, browser: true */
            /* globals QBMediaRecorder, Promise */

            'use strict';

            var notify = {
                ui: document.querySelector('.j-notify'),
                hide: function() {
                    this.ui.classList.remove('notify-active');
                },
                show: function(txt) {
                    var n = this;

                    n.ui.textContent = txt;
                    n.ui.classList.add('notify-active');

                    var timerId = setTimeout(function() {
                        n.hide();
                    }, 5000);
                }
            };

            var resultCard = {
                blob: null, // saved a blob after stopped a record
                ui: {
                    wrap: document.querySelector('.j-result-card'),
                    video: document.querySelector('.j-video_result'),
                    clear: document.querySelector('.j-clear'),
                    download: document.querySelector('.j-download')
                },
                toggleBtn: function(state) {
                    this.ui.clear.disabled = state;
                    this.ui.download.disabled = state;
                },
                attachVideo: function(blob) {
                    this.ui.video.src = URL.createObjectURL(blob);

                    this.ui.clear.disabled = false;
                    this.ui.download.disabled = false;
                },
                detachVideo: function() {
                    this.blob = null;
                    this.ui.video.src = '';

                    this.ui.clear.disabled = true;
                    this.ui.download.disabled = true;
                },
                setupListeners: function(rec) {
                    var self = this;

                    var evClear = new CustomEvent('clear');
                    var evDownload = new CustomEvent('download');

                    self.ui.clear.addEventListener('click', function() {
                        self.ui.video.pause();
                        self.detachVideo();

                        self.ui.wrap.dispatchEvent(evClear);
                    });

                    self.ui.download.addEventListener('click', function() {
                        self.ui.wrap.dispatchEvent(evDownload);
                    });
                }
            };

            var inputCard = {
                stream: null,
                devices: {
                    audio: [],
                    video: []
                },
                ui: {
                    wrap: document.querySelector('.j-card'),
                    video: document.querySelector('.j-video_local'),

                    start: document.querySelector('.j-start'),
                    stop: document.querySelector('.j-stop'),
                    pause: document.querySelector('.j-pause'),
                    resume: document.querySelector('.j-resume'),

                    selectAudioSource: document.getElementById('j-audioSource'),
                    selectVideoSource: document.getElementById('j-videoSource'),
                },
                _createOptions: function(type) {
                    var docfrag = document.createDocumentFragment();

                    /* create a default option */
                    var optDef = document.createElement('option');
                    optDef.textContent = `Choose an input ${type}-device`;
                    optDef.value = 'default';

                    docfrag.appendChild(optDef);

                    /* create a options with available sources */
                    this.devices[type].forEach(function(device, index) {
                        var option = document.createElement('option');

                        option.value = device.deviceId;
                        option.textContent = device.label || `${index + 1} ${type} source`;

                        docfrag.appendChild(option);
                    });

                    /* create a option which off a type a media */
                    var optOff = document.createElement('option');
                    optOff.textContent = `Off ${type} source`;
                    optOff.value = 0;

                    docfrag.appendChild(optOff);

                    return docfrag;
                },
                _processDevices: function(devices) {
                    var self = this;

                    var docfragAudio = document.createDocumentFragment(),
                        docfragVideo = document.createDocumentFragment()

                    devices.forEach(function(device) {
                        if(device.kind.indexOf('input') !== -1) {
                            if(device.kind === 'audioinput') {
                                /* set audio source to collection */
                                self.devices.audio.push(device);
                            }

                            if(device.kind === 'videoinput') {
                                /* set video source to collection */
                                self.devices.video.push(device);
                            }
                        }
                    });

                    if(self.devices.audio.length > 0) {
                        self.ui.selectAudioSource.appendChild( self._createOptions('audio') );
                        self.ui.selectAudioSource.classList.remove('invisible');
                    }

                    if(self.devices.video.length > 0) {
                        self.ui.selectVideoSource.appendChild( self._createOptions('video') );
                        self.ui.selectVideoSource.classList.remove('invisible');
                    }
                },
                getDevices: function() {
                    var self = this;

                    navigator.mediaDevices.enumerateDevices()
                        .then(function(devices) {
                            self._processDevices(devices);
                        })
                },
                attachStreamToSource: function() {
                    this.ui.video.pause();
                    this.ui.video.src='';

                    this.ui.video.src = URL.createObjectURL(this.stream);
                    this.ui.video.play();
                },
                getUserMedia: function(attrs) {
                    var constraints = attrs || { audio: true, video: true };

                    return navigator.mediaDevices.getUserMedia(constraints)
                },
                _getSources: function() {
                    var sVideo = this.ui.selectVideoSource,
                        sAudio = this.ui.selectAudioSource,
                        selectedAudioSource = sAudio.options[sAudio.selectedIndex].value,
                        selectedVideoSource = sVideo.options[sVideo.selectedIndex].value;

                    var constraints = {};

                    if(selectedAudioSource === 'default') {
                        constraints.audio = true;
                    } else if(selectedAudioSource === '0') {
                        constraints.audio = false;
                    } else {
                        constraints.audio = {deviceId: selectedAudioSource};
                    }

                    if(selectedVideoSource === 'default') {
                        constraints.video = true;
                    } else if(selectedVideoSource === '0') {
                        constraints.video = false;
                    } else {
                        constraints.video = {deviceId: selectedVideoSource};
                    }

                    return constraints;
                },
                _stopStreaming: function() {
                    this.stream.getTracks().forEach(function(track) {
                        track.stop();
                    });
                },
                _setupListeners: function() {
                    var self = this;

                    var evStart = new CustomEvent('started');
                    var evPause = new CustomEvent('paused');
                    var evResume = new CustomEvent('resumed');
                    var evStop = new CustomEvent('stopped');
                    var evChange = new CustomEvent('changed');

                    self.ui.start.addEventListener('click', function() {
                        self.ui.start.disabled = true;
                        self.ui.resume.disabled = true;

                        self.ui.stop.disabled = false;
                        self.ui.pause.disabled = false;

                        self.ui.wrap.dispatchEvent(evStart);
                    });

                    self.ui.stop.addEventListener('click', function() {
                        self.ui.start.disabled = false;

                        self.ui.stop.disabled = true;
                        self.ui.pause.disabled = true;
                        self.ui.resume.disabled = true;

                        self.ui.wrap.dispatchEvent(evStop);
                    });

                    self.ui.pause.addEventListener('click', function() {
                        self.ui.start.disabled = true;
                        self.ui.pause.disabled = true;

                        self.ui.resume.disabled = false;
                        self.ui.stop.disabled = false;

                        self.ui.wrap.dispatchEvent(evPause);
                    });

                    self.ui.resume.addEventListener('click', function() {
                        self.ui.start.disabled = true;
                        self.ui.resume.disabled = true;

                        self.ui.pause.disabled = false;
                        self.ui.stop.disabled = false;

                        self.ui.wrap.dispatchEvent(evResume);
                    });

                    function handleSources() {
                        var constrains = self._getSources();

                        self._stopStreaming();
                        self.stream = null;

                        self.getUserMedia(constrains).then(function(stream) {
                            self.stream = stream;
                            self.attachStreamToSource();

                            self.ui.wrap.dispatchEvent(evChange);
                        });
                    }

                    self.ui.selectAudioSource.addEventListener('change', handleSources);
                    self.ui.selectVideoSource.addEventListener('change', handleSources);
                },
                init: function() {
                    var self = this;

                    return new Promise(function(resolve, reject) {
                        self.getUserMedia()
                            .then(function(stream) {
                                self.stream = stream;
                                self.attachStreamToSource();
                                self.getDevices();
                                self._setupListeners();

                                resolve();
                            }).catch(function(error) {
                            reject(error);
                        })
                    });
                }
            };

            /* Start !FUN */
            inputCard.init()
                .then(function() {
                    var opts = {
                        onstop: function onStoppedRecording(blob) {
                            resultCard.blob = blob;
                            resultCard.attachVideo(blob);
                        }
                    };

                    var rec = new QBMediaRecorder(opts);

                    resultCard.setupListeners();

                    inputCard.ui.wrap.addEventListener('started', function() {
                        rec.start(inputCard.stream);
                    }, false);

                    inputCard.ui.wrap.addEventListener('paused', function() {
                        rec.pause();
                    }, false);

                    inputCard.ui.wrap.addEventListener('resumed', function() {
                        rec.resume();
                    }, false);

                    inputCard.ui.wrap.addEventListener('changed', function() {
                        if(rec.getState() === 'recording') {
                            rec.change(inputCard.stream);
                        }
                    }, false);

                    inputCard.ui.wrap.addEventListener('stopped', function() {
                        rec.stop();
                        resultCard.toggleBtn(false);
                    }, false);

                    resultCard.ui.wrap.addEventListener('download', function() {
                        rec.download(null, resultCard.blob);
                    }, false);
                })
                .catch(function(error) {
                    notify.show(`Error: ${error.name}`);
                });
        }
    }
</script>