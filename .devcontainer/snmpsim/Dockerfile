FROM python:3.12-slim

# Keep in sync with LibreNMS\Util\Snmpsim::setupVenv()
RUN pip install --no-cache-dir snmpsim==1.1.7 \
    && useradd --system --no-create-home snmpsim

USER snmpsim
EXPOSE 1161/udp

ENTRYPOINT ["snmpsim-command-responder-lite", "--data-dir=/snmpsim", "--agent-udpv4-endpoint=0.0.0.0:1161", "--log-level=error"]
