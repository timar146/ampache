import React, { useContext, useState } from 'react';
import SVG from 'react-inlinesvg';
import { PLAYERSTATUS } from '~enum/PlayerStatus';
import { MusicContext } from '~Contexts/MusicContext';
import Slider from '@material-ui/core/Slider';
import CurrentPlaying from '~components/CurrentPlaying/';
import CurrentPlayingArt from '~components/CurrentPlayingArt/';
import Rating from '~components/Rating/';
import { flagSong } from '~logic/Song';
import { AuthKey } from '~logic/Auth';
import { toast } from 'react-toastify';
import AmpacheError from '~logic/AmpacheError';

interface MusicControlProps {
    authKey?: AuthKey;
}

const MusicControl: React.FC = (props) => {
    const musicContext = useContext(MusicContext);

    const [ratingToggle, setRatingToggle] = useState(false);

    const [isSeeking, setIsSeeking] = useState(false);
    const [seekPosition, setSeekPosition] = useState(-1);

    const [error, setError] = useState<Error | AmpacheError>(null);

    const handleRatingToggle = () => {
        if (
            musicContext.currentPlayingSong !== undefined &&
            musicContext.currentPlayingSong !== null
        ) {
            setRatingToggle(!ratingToggle);
        }
    };

    const handleFlagSong = (songID: number, favorite: boolean) => {
        flagSong(songID, favorite, props.authKey)
            .then(() => {
                musicContext.toggleFlag();
                if (favorite) {
                    return toast.success('Song added to favorites');
                }
                toast.success('Song removed from favorites');
            })
            .catch((err) => {
                if (favorite) {
                    toast.error(
                        '😞 Something went wrong adding song to favorites.'
                    );
                } else {
                    toast.error(
                        '😞 Something went wrong removing song from favorites.'
                    );
                }
                setError(err);
            });
    };

    const formatLabel = (s) => [
        (s - (s %= 60)) / 60 + (9 < s ? ':' : ':0') + s
        //https://stackoverflow.com/a/37770048
    ];

    return (
        <div className={`musicControl ${ratingToggle ? 'ratingShown' : null}`}>
            <CurrentPlayingArt />

            <CurrentPlaying />

            <div className='ratingBarContainer'>
                <div className='ratingBar'>
                    <Rating
                        value={
                            musicContext.currentPlayingSong
                                ? musicContext.currentPlayingSong.rating
                                : 0
                        }
                        fav={
                            musicContext.currentPlayingSong
                                ? musicContext.currentPlayingSong.flag
                                : 0
                        }
                        song={musicContext.currentPlayingSong}
                        flagSong={handleFlagSong}
                    />
                </div>
            </div>

            <div className='seekbar'>
                <Slider
                    min={0}
                    max={musicContext.currentPlayingSong?.time ?? 0}
                    value={isSeeking ? seekPosition : musicContext.songPosition}
                    onChangeCommitted={(_, value: number) => {
                        // setIsSeeking(true);
                        // setValue(value);
                        // setSeekPosition(value);
                        musicContext.seekSongTo(value);
                        setIsSeeking(false);
                    }}
                    onChange={(_, value: number) => {
                        setIsSeeking(true);
                        setSeekPosition(value);
                    }}
                    disabled={musicContext.currentPlayingSong == undefined}
                    aria-labelledby='continuous-slider'
                />
            </div>

            <div className='seekTimes'>
                <span className='seekStart'>
                    {formatLabel(musicContext.songPosition)}
                </span>
                <span className='seekEnd'>
                    {formatLabel(musicContext.currentPlayingSong?.time ?? 0)}
                </span>
            </div>

            <div className='controls'>
                <div className='previousSong'>
                    <SVG
                        src={require('~images/icons/svg/previous-track.svg')}
                        title='Previous'
                        description='Play previous song'
                        role='button'
                        aria-disabled={musicContext.songQueueIndex <= 0}
                        onClick={() => {
                            musicContext.playPrevious();
                        }}
                        className={`
                            icon-button 
                            ${
                                musicContext.songQueueIndex <= 0
                                    ? 'disabled'
                                    : ''
                            }
                        `}
                    />
                </div>
                <div className='playPause'>
                    {musicContext.playerStatus === PLAYERSTATUS.STOPPED ||
                    musicContext.playerStatus === PLAYERSTATUS.PAUSED ? (
                        <SVG
                            src={require('~images/icons/svg/play.svg')}
                            title='Play'
                            description='Resume music'
                            role='button'
                            aria-disabled={
                                musicContext.currentPlayingSong == undefined
                            }
                            onClick={musicContext.playPause}
                            className={`
                                icon-button 
                                ${
                                    musicContext.currentPlayingSong == undefined
                                        ? 'disabled'
                                        : ''
                                }
                            `}
                        />
                    ) : (
                        <SVG
                            src={require('~images/icons/svg/pause.svg')}
                            title='Pause'
                            description='Pause music'
                            role='button'
                            onClick={musicContext.playPause}
                            aria-disabled={
                                musicContext.currentPlayingSong == undefined
                            }
                            className={`
                                icon-button 
                                ${
                                    musicContext.currentPlayingSong == undefined
                                        ? 'disabled'
                                        : ''
                                }
                            `}
                        />
                    )}
                </div>
                <div className='nextSong'>
                    <SVG
                        src={require('~images/icons/svg/next-track.svg')}
                        title='Next'
                        description='Play next song'
                        role='button'
                        aria-disabled={
                            musicContext.songQueueIndex ==
                            musicContext.songQueue.length - 1
                        }
                        onClick={() => {
                            musicContext.playNext();
                        }}
                        className={`
                            icon-button 
                            ${
                                musicContext.songQueueIndex ==
                                musicContext.songQueue.length - 1
                                    ? 'disabled'
                                    : ''
                            }
                        `}
                    />
                </div>
            </div>

            <div className='secondaryControls'>
                <div className={`rating ${ratingToggle ? 'active' : null}`}>
                    <SVG
                        src={require('~images/icons/svg/star-full.svg')}
                        title='Show ratings'
                        role='button'
                        onClick={() => {
                            handleRatingToggle();
                        }}
                        className='icon-button'
                    />
                </div>

                <div className='shuffle'>
                    <SVG
                        src={require('~images/icons/svg/shuffle.svg')}
                        title='Shuffle'
                        description='Shuffle queued songs'
                        role='button'
                        onClick={() => {
                            // TODO: shuffle;
                        }}
                        className='icon-button'
                    />
                </div>

                <div className='repeat'>
                    <SVG
                        src={require('~images/icons/svg/repeat.svg')}
                        title='Repeat'
                        description='Repeat the current song'
                        onClick={() => {
                            // TODO: repeat;
                        }}
                        className='icon-button'
                    />
                </div>

                <div className='moreOptions'>
                    <SVG
                        src={require('~images/icons/svg/more-options-hori.svg')}
                        title='More options'
                        role='button'
                        onClick={() => {
                            // TODO: open more options menu;
                        }}
                        className='icon-button'
                    />
                </div>
            </div>

            <div className='volumeSlide'>
                <SVG
                    src={require('~images/icons/svg/volume-up.svg')}
                    title='Mute'
                    description='Mute the music'
                    role='button'
                    onClick={() => {
                        musicContext.setVolume(0); //TODO: Unmute? Store old volume level?
                    }}
                    className='icon-button'
                />
                <Slider
                    name='volume'
                    onChange={(_, value: number) => {
                        musicContext.setVolume(value);
                    }}
                    max={100}
                    min={0}
                    value={musicContext.volume}
                />
            </div>
        </div>
    );
};

export default MusicControl;
