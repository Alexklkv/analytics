<?php

	class BottleGoals
	{
		// Server-side
		const GOAL_BUY_BOTTLES = 0;
		const GOAL_BUY_SUBSCRIBE = 1;
		const GOAL_BUY_SHOWS = 2;
		const GOAL_BUY_BOX = 3;

		const GOAL_RESERVED_2 = 4;
		const GOAL_RESERVED_3 = 5;
		const GOAL_RESERVED_4 = 6;
		const GOAL_RESERVED_5 = 7;

		// Client-side
		const MESSAGE_ROOM_SEND = 8;
		const MESSAGE_WHISPER_SEND = 9;
		const KISS_YES_SEND = 10;
		const HEART_SEND = 11;
		const GIFT_GET = 12;
		const HEART_GET = 13;
		const KISS_REFUSE_GET = 14;
		const KISS_MUTABLE_BOTTLE = 15;
//		const BUY_BOTTLES = 16;		// Free; server uses < 8
//		const BUY_VIP = 17;		// Free, server uses < 8
//		const BUY_SHOWS = 18;		// Free, server uses < 8
		const STICKER_SEND = 19;
		const EMOTION_SEND = 20;
		const SMILE_SEND = 21;
		const GIFT_SEND = 22;
		const ADMIRE_START = 23;
		const ADMIRE_GET = 24;
		const SPIN_BOTTLE_CASUAL = 25;
		const RATE_PLAYER_PHOTO = 26;
		const SPIN_ROULETTE = 27;
		const SEND_KISS_YES_ROULETTE = 28;
		const KISS_MUTABLE_ROULETTE = 29;
		const RECEIVE_PHOTO_RATE = 30;
		const CHANGE_STATUS = 31;
		const CHANGE_PHOTO = 32;
		const LOAD_MANY_PHOTOS = 33;
		const ADD_FRIEND = 34;
		const INVITE_FRIENDS = 35;
		const GET_PET = 36;
		const SECOND_DAY_ENTER = 37;
		const WEEK_ENTER = 38;
		const BUY_TAPE_TOP = 39;
		const BUY_WANT_TALK = 40;
		const GET_WHISPER_MESSAGE = 41;
		const GET_FREE_SHOW = 42;
	}

?>