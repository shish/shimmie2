﻿/**    
 * @author Jefferson González
 * @copyright 2010 Jefferson González
 *
 * @license 
 * This file is part of Jaris FLV Player.
 *
 * Jaris FLV Player is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License or GNU LESSER GENERAL 
 * PUBLIC LICENSE as published by the Free Software Foundation, either version 
 * 3 of the License, or (at your option) any later version.
 *
 * Jaris FLV Player is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License and 
 * GNU LESSER GENERAL PUBLIC LICENSE along with Jaris FLV Player.  If not, 
 * see <http://www.gnu.org/licenses/>.
 */

package jaris.player;

//{Libraries
import jaris.events.PlayerEvents;
//}

/**
 * Implements a loop mechanism on the player
 */
class Loop 
{
	private var _player:Player;
	
	public function new(player:Player) 
	{
		_player = player;
		_player.addEventListener(PlayerEvents.PLAYBACK_FINISHED, onPlayerStop);
	}
	
	/**
	 * Everytime the player stops, the playback is restarted
	 * @param	event
	 */
	private function onPlayerStop(event:PlayerEvents):Void
	{
		_player.togglePlay();
	}
}