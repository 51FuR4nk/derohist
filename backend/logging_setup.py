import logging, os, sys

def setup_logging():
    level = os.getenv("LOG_LEVEL", "INFO").upper()
    handler = logging.StreamHandler(sys.stdout)
    logging.basicConfig(
        level=getattr(logging, level, logging.INFO),
        handlers=[handler],
        format="%(asctime)s %(levelname)s %(name)s: %(message)s",
    )