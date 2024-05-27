from poe_api_wrapper import PoeApi
import sys

tokens = {
    'b': "p-b token here", # replace with your token
    'lat': "p-lat token here" # replace with your token
}

bot = sys.argv[1]
prompt = sys.argv[2]

client = PoeApi(cookie=tokens)
for chunk in client.send_message(bot, prompt):
    pass

print(chunk["text"])