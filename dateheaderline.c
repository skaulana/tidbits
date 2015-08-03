/*  It was frustrating that I couldn't figure out what day a conversation started.
 *  And Pidgin didn't help. So I had to do it myself. Result: this plugin.
 *  Learn more @ http://www.kaulana.com/
 */

#ifdef HAVE_CONFIG_H
# include <config.h>
#endif

#ifndef PURPLE_PLUGINS
# define PURPLE_PLUGINS
#endif

#include <glib.h>

#ifndef G_GNUC_NULL_TERMINATED
# if __GNUC__ >= 4
#  define G_GNUC_NULL_TERMINATED __attribute__((__sentinel__))
# else
#  define G_GNUC_NULL_TERMINATED
# endif
#endif

#include <notify.h>
#include <plugin.h>
#include <version.h>

#include "conversation.h"
#include "signals.h"
#include "util.h"

PurplePlugin *dateheaderline_plugin = NULL;

static void dateheaderline(PurpleConversation *gconv)
{
      purple_conversation_write(
		gconv,
		NULL,
		purple_utf8_strftime("Today is %B %d, %Y.", NULL),
		PURPLE_MESSAGE_SYSTEM | PURPLE_MESSAGE_ACTIVE_ONLY,
		time(NULL)
	);
}

static gboolean
plugin_load (PurplePlugin * plugin)
{
	purple_signal_connect(
		purple_conversations_get_handle(),
		"conversation-created",
		plugin,
		PURPLE_CALLBACK(dateheaderline),
		NULL
	);

	dateheaderline_plugin = plugin;
	return TRUE;
}

static PurplePluginInfo info = {
	PURPLE_PLUGIN_MAGIC,
	PURPLE_MAJOR_VERSION,
	PURPLE_MINOR_VERSION,
	PURPLE_PLUGIN_STANDARD,
	NULL,
	0,
	NULL,
	PURPLE_PRIORITY_DEFAULT,

	"core-date_header_line",
	"Date Header Line",
	"0.1",

	"Adds the current date to the top of new conversation windows.",
	"Since Pidgin only notes the date when it changes, it can be difficult to determine when a conversation started. Hence this plugin.",
	"Sean Kaulana <s@kaulana.com>",
	"http://www.kaulana.com/",

	plugin_load,
	NULL,
	NULL,
	NULL,
	NULL,
	NULL,
	NULL,
	NULL,
	NULL,
	NULL,
	NULL
};

static void
init_plugin (PurplePlugin * plugin)
{
}

PURPLE_INIT_PLUGIN (date_header_line, init_plugin, info)
